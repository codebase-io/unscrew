<?php

namespace Unscrew\Parser;

interface ParserInterface
{
    // TODO get formats

    public function parse($stream, ?string $documentRoot=NULL, ?string $documentId=NULL ): array;
}
