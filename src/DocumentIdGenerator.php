<?php

namespace Unscrew;

use Symfony\Component\HttpFoundation\Request;

interface DocumentIdGenerator
{
    public function generate(Request $request, string $filename): ?string;
}
