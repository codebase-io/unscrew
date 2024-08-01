<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Unscrew\Unscrew;
use Unscrew\Parser\DefaultParser;
use Unscrew\DefaultDocumentIdGenerator;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use Symfony\Component\String\Slugger\AsciiSlugger;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;


// Configure the Environment with all the CommonMark and GFM parsers/renderers
$environment = new Environment([]);
$environment->addExtension(new TableExtension());
$environment->addExtension(new TaskListExtension());
$environment->addExtension(new AutolinkExtension());
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new GithubFlavoredMarkdownExtension());

$converter = new MarkdownConverter($environment);
// TODO rename to resolver
$idGenerator= new DefaultDocumentIdGenerator(new AsciiSlugger(), '');

// Setup CMS with default parser
$unscrew = Unscrew::withParser(new DefaultParser($converter));
$unscrew->setMarkdownHtmlConverter($converter);
$unscrew->setDocumentIdGenerator($idGenerator);

// /example -> example.md | /example/index.md | /example/example.md
// TODO throw when multiple paths are found for the same route

// Serve
// TODO support flysystem instead of folder
$unscrew->serve(__DIR__ . '/../storage');