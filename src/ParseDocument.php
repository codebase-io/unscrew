<?php

namespace Unscrew;

use League\CommonMark\Exception\CommonMarkException;
use League\Config\Exception\ConfigurationExceptionInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ParseDocument
{
    /**
     * @throws ConfigurationExceptionInterface
     * @throws FilesystemException
     * @throws CommonMarkException
     */
    private function parseDocument(
        Document $doc,
        FilesystemOperator $filesystem,
        ?string $format=self::FORMAT_JSON,
    ): Response
    {
        $headers = [
            'X-Document-Id'     => $doc->getId(),
            'X-Document-Root'   => $doc->getRoot(),
            'X-Document-Format' => $format,
        ];

        if (!$format || self::FORMAT_JSON == $format) {
            // Markdown -> JSON (default)
            // TODO return type for Parser should be Response
            $stream = $filesystem->readStream($doc->getFilename());
            $data   = $this->parser->parse($stream, $doc->getRoot(), $doc->getId());
            return new JsonResponse($data, 200, $headers);
        }

        if (self::FORMAT_HTML == $format) {
            // Markdown -> HTML

            if (!isset($this->markdownConverter)) {
                throw new RuntimeException("No markdown converter configured.");
            }

            $rndr = $this->markdownConverter->convert($filesystem->read($doc->getFilename()));
            $html = str_replace('{title}', basename($doc->getFilename()), $this->htmlTemplate);
            $html = str_replace('{content}', $rndr->getContent(), $html);
            $headers['Content-Type'] = 'text/html';

            return new Response($html, 200, $headers);
        }

        // TODO prob. throw on un-parsable format
    }
}
