<?php

namespace Unscrew;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;
use Unscrew\Parser\ParserInterface;
use Safe\Exceptions\FilesystemException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Unscrew
{
    // TODO have a cache of resolved paths
    private function __construct(private ParserInterface $parser) {}

    public static function withParser( ParserInterface $parser): static
    {
        return new static($parser);
    }

    /**
     * @throws FilesystemException
     */
    private function getFilenameByPath(string $pathinfo): string
    {
        $variants = [
            "$pathinfo.md",
            $pathinfo . DIRECTORY_SEPARATOR . "index.md",
            $pathinfo . DIRECTORY_SEPARATOR . basename($pathinfo) . 'md',
        ];

        foreach ($variants as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException("File not found for path.");
    }

    private function prepare(string $filename): Response
    {
        // TODO support caching
        $data = $this->parser->parse($filename);
        return new JsonResponse($data);
    }

    public function serve(string $folder): void
    {
        $folder = rtrim($folder, DIRECTORY_SEPARATOR);
        $request = Request::createFromGlobals();
        $path    = ($folder. DIRECTORY_SEPARATOR . trim($request->getPathInfo(), '/'));

        if (!is_dir($folder) or !is_readable($folder)) {
            throw new InvalidArgumentException("Folder to serve should be a directory and should be readable.");
        }

        try {
            $filename = $this->getFilenameByPath($path);
            $response = $this->prepare($filename);
        }
        catch ( Throwable $exception) {
            // TODO better treatment of errors
            $response = new JsonResponse([
                'document'=> $filename ?? NULL,
                'path'    => $request->getPathInfo(),
                'error'   => $exception->getMessage(),
                'trace'   => $exception->getTrace(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response->send(TRUE);
    }
}
