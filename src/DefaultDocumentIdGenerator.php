<?php

namespace Unscrew;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class DefaultDocumentIdGenerator
 *
 * Generates a document id by slugging the path
 *
 * Implements the DocumentIdGenerator interface.
 */
class DefaultDocumentIdGenerator implements DocumentIdGenerator
{

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $prefix='',
    ) {}

    public function generate(Request $request, string $filename): ?string
    {
        $fpath = str_replace('/index.md', '', $filename);
        return $this->prefix . $this->slugger->slug($fpath);
    }
}
