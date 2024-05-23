<?php

namespace Unscrew\Parser;

interface ParserInterface
{
    public function parse(string $filename, ?string $documentRoot=NULL, ?string $documentId=NULL ): array;
}
