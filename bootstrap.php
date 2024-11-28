<?php /** @noinspection ALL */

use Unscrew\Config;
use Unscrew\Unscrew;

require_once __DIR__ . '/vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';
$config = Config::fromArray($config);

// Setup CMS with parser
$unscrew = Unscrew::withParser($config->getParserToJson());

if ($htmlParser = $config->getParserToHtml())
    $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

if ($defaultFormat = $config->getDefaultFormat())
    $unscrew->setDefaultFormat($defaultFormat);

if ($docIdGenerator = $config->getDocumentIdGenerator())
    $unscrew->setDocumentIdGenerator($docIdGenerator);
