<?php

namespace Unscrew;

use InvalidArgumentException;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\Config\Exception\ConfigurationExceptionInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use Unscrew\Parser\ParserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Unscrew
{
    const DEFAULT_HTML_TPL = <<<HTML
        <html>
            <head>
                <title>{title}</title>
                <style media="all">
                    body{
                        background: #fdf6e3;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
                    }
                    img { max-width: 100%; max-height: 100%; display: block; }
                    article { width: 71vw; background: #eee8d5; padding: 24px; border-radius: 3px; }
                </style>
                <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/styles/default.min.css">
                <script src="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/highlight.min.js"></script>
            </head>
            <body>
                <article>{content}</article>
                <script>hljs.highlightAll();</script>
            </body>
        </html>
    HTML;

    const FORMAT_JSON = 'json';
    const FORMAT_HTML = 'html';

    private MimeTypes $mimeTypes;

    private function __construct(
        private readonly ParserInterface $parser,
        private string $defaultFormat = self::FORMAT_JSON,
        private ?ConverterInterface $markdownConverter = NULL,
        private ?DocumentIdGenerator $idGenerator = NULL,
        // TODO support custom html template
        private string $htmlTemplate = self::DEFAULT_HTML_TPL,
    ) {
        $this->mimeTypes = new MimeTypes();
    }

    public static function withParser(ParserInterface $parser): static
    {
        return new static($parser);
    }

    public function setDefaultFormat(string $format): void
    {
        if (!in_array($format, ['md', 'html', 'json'])) {
            // TODO is MD really supported as default?
            throw new InvalidArgumentException(sprintf("Format %s not supported as default.", $format));
        }

        $this->defaultFormat = $format;
    }

    public function setDocumentIdGenerator(DocumentIdGenerator $generator): void
    {
        $this->idGenerator = $generator;
    }

    public function setMarkdownHtmlConverter(ConverterInterface $converter): void
    {
        $this->markdownConverter = $converter;
    }

    public function setHtmlTemplate(string $template): void
    {
        $this->htmlTemplate= $template;
    }

    private function getDocumentRoot(Request $request, string $folder, string $filename): string
    {
        $suffix = str_replace($folder, '', dirname($filename));
        return $request->getSchemeAndHttpHost() . $suffix;
    }

    private function fileIsMarkdown(string $mime, bool $break=FALSE): bool
    {
        $mime= strtolower($mime);

        if ( str_contains( $mime, 'markdown' ) ) {
            return TRUE;
        }

        if ($break) {
            return FALSE;
        }

        $extensions= join('/', $this->mimeTypes->getExtensions($mime));

        return $this->fileIsMarkdown($extensions, TRUE);
    }

    /**
     * Given request path, resolves filename on filesystem
     * @throws FilesystemException
     */
    private function getFilenameByPath(
        FilesystemOperator $filesystem,
        string $pathinfo,
        ?string $ext
    ): string
    {
        $ext  = $ext ?? 'md'; // TODO Markdown files may come with different extensions

        $pathinfo = $pathinfo === DIRECTORY_SEPARATOR ? "" : $pathinfo;
        $bpath    = mb_substr($pathinfo, 0, mb_strrpos($pathinfo, '.'));

        $variants = [
            $pathinfo,
            "$bpath.md",
            "{$pathinfo}.{$ext}",
            $pathinfo . DIRECTORY_SEPARATOR . "index.{$ext}",
            $pathinfo . DIRECTORY_SEPARATOR . basename($pathinfo) . ".$ext",
        ];

        $variants = array_filter($variants);
        $variants = array_filter($variants, fn($var) => ".{$ext}" !== trim($var, '/') );

        foreach ($variants as $path) {
            if ($filesystem->fileExists($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException(sprintf("404 - File not found for path %s.", $pathinfo));
    }

    private function respondWithError(
        array $info,
        string $format=self::FORMAT_JSON,
        int $status=400,
    ): Response
    {
        if (self::FORMAT_JSON === $format) {
            return new JsonResponse($info, $status);
        }

        $title = sprintf("<title>%s</title>", $info['error'] ?? "Unknown error");
        $body = sprintf(
            <<<HTML
                <body style='text-align: center; font-family: monospace;'>
                    <h1>404</h1>
                    <p>Could not find a Markdown document matching path `%s`.</p>
                </body>
            HTML,
                $info['path'] ?? "",
        );

        return new Response("<html lang='en'>{$title} {$body}</html>", $status);
    }

    /**
     * @throws ConfigurationExceptionInterface
     * @throws CommonMarkException
     * @throws FilesystemException
     */
    private function prepare(
        FilesystemOperator $filesystem,
        string $filename,
        ?string $format=self::FORMAT_JSON,
        ?string $docId=NULL,
        ?string $docroot=NULL,
    ): Response
    {
        $headers = [
            'X-Document-Id'     => $docId,
            'X-Document-Root'   => $docroot,
            'X-Document-Format' => $format,
        ];

        $mime   = $filesystem->mimeType($filename);

        if ($this->fileIsMarkdown($mime)) {
            if (!$format || self::FORMAT_JSON == $format) {
                // Markdown -> JSON (default)
                $stream = $filesystem->readStream($filename);
                $data   = $this->parser->parse($stream, $docroot, $docId);
                return new JsonResponse($data, 200, $headers);
            }

            if (self::FORMAT_HTML == $format) {
                // Markdown -> HTML

                if (!isset($this->markdownConverter)) {
                    throw new RuntimeException("No markdown converter configured.");
                }

                $rndr = $this->markdownConverter->convert($filesystem->read($filename));
                $html = str_replace('{title}', basename($filename), $this->htmlTemplate);
                $html = str_replace('{content}', $rndr->getContent(), $html);
                $headers['Content-Type'] = 'text/html';

                return new Response($html, 200, $headers);
            }
        }

        // Serve other files as binary
        $stream = $filesystem->readStream($filename);
        $resp   = new StreamedResponse(
            fn() => fpassthru($stream),
            200,
            $headers,
        );
        $resp->headers->set('Content-Type', $mime);

        return $resp;

    }

    /**
     * @throws FilesystemException
     */
    protected function process(FilesystemOperator $filesystem, Request $request): Response
    {
        $rqpath = $request->getPathInfo();
        $path   = (DIRECTORY_SEPARATOR . trim($rqpath, '/'));

        $ext    = str_contains($rqpath, '.') ? mb_substr($rqpath, mb_strripos($rqpath, '.')+1) : NULL;
        $format = $ext ?? $this->defaultFormat;

        if (!$filesystem->directoryExists('/')) {
            throw new InvalidArgumentException("Path to serve should be a directory and should be readable.");
        }

        try {
            $filename = $this->getFilenameByPath($filesystem, $path, $ext);

            return $this->prepare(
                $filesystem,
                $filename,
                $format,
                $this->idGenerator?->generate($request, $filename),
                $this->getDocumentRoot($request, dirname($filename), $filename),
            );
        }
        catch (InvalidArgumentException $exception) {
            $status = Response::HTTP_NOT_FOUND;
        }
        catch ( Throwable $exception) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $this->respondWithError([
            'document'=> $filename ?? NULL,
            'path'    => $request->getPathInfo(),
            'error'   => $exception?->getMessage(),
        ], $format, $status);
    }

    /**
     * @throws FilesystemException
     * @codeCoverageIgnore
     */
    public function serve(FilesystemOperator $filesystem, Request $request): void
    {
        $this->process($filesystem, $request)->send();
    }
}
