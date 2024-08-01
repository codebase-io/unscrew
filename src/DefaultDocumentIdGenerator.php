<?php

namespace Unscrew;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class DefaultDocumentIdGenerator implements DocumentIdGenerator
{

    public function __construct(
        private SluggerInterface $slugger,
        private string $prefix='',
    ) {}

    public function generate( Request $request, string $filename, string $folder ): ?string
    {
        $fpath = str_replace($folder, '', $filename);
        $fpath = str_replace('/index.md', '', $fpath);

        return $this->prefix . $this->slugger->slug($fpath);
    }
}
