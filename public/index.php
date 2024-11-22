<?php

require_once __DIR__ . '/../src/bootstrap.php';

use Symfony\Component\HttpFoundation\Request;

global $unscrew, $config;

// Serve
$request = Request::createFromGlobals();
$unscrew->serve($config->getFilesystem(), $request);
