<?php

namespace Unscrew;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;

class FlysystemChecksumIdGenerator implements DocumentIdGenerator
{
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly array $config = [],
    ) {}

    public function generate( Request $request, string $filename ): ?string {
        try {
            return $this->filesystem->checksum($filename, $this->config);
        }
        catch (\Exception $exception) {
            throw new \RuntimeException( 'Checksum() is not supported on this filesystem. See: https://flysystem.thephpleague.com/docs/usage/checksums/', 0, $exception );
        }
    }
}
