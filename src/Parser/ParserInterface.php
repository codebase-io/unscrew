<?php

namespace Unscrew\Parser;

interface ParserInterface
{
    public function parse(string $filename): array;
}
