<?php

namespace Unscrew;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ServeStatic
{
    /**
     * @throws FilesystemException
     */
    private function serveStatic(
        FilesystemOperator $filesystem,
        string $filename,
        ?string $mime,
    ): StreamedResponse
    {
        // Serve other files as binary
        $stream = $filesystem->readStream($filename);
        $resp   = new StreamedResponse( fn() => fpassthru($stream), 200);

        $mime && $resp->headers->set('Content-Type', $mime);

        return $resp;
    }
}
