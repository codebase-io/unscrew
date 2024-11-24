<?php

namespace Unscrew\Parser;

interface ParserInterface
{
    public function parse($stream, ?string $documentRoot=NULL, ?string $documentId=NULL ): array;
}
