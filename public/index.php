<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Unscrew\Unscrew;
use Unscrew\Parser\DefaultParser;


$unscrew = Unscrew::withParser(new DefaultParser(new \League\CommonMark\CommonMarkConverter()));

// /example -> example.md | /example/index.md | /example/example.md
// TODO throw when multiple paths are found for the same route
// /folder/resource ->
$unscrew->serve(__DIR__ . '/../content');
