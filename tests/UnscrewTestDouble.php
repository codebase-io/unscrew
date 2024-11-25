<?php

namespace Tests;

use Unscrew\Unscrew;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UnscrewTestDouble extends Unscrew {
    public function process(
        FilesystemOperator $filesystem,
        Request $request
    ): Response
    {
        return parent::process($filesystem, $request);
    }
}
