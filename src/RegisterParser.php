<?php

namespace Unscrew;

trait RegisterParser
{
    protected array $parsers = [];

    // TODO support registering more parsers

    protected function canParseTo(string $format) : bool
    {
        if ('html' == $format and isset($this->markdownConverter)) {
            return TRUE;
        }

        if ('json' == $format and isset($this->parser)) {
            return TRUE;
        }

        return FALSE;
    }
}
