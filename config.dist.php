<?php
/**
 * Unscrew configuration file;
 * Configure and rename this file to config.php;
 */

use League\Flysystem\Filesystem;
use Unscrew\Parser\DefaultParser;
use Unscrew\DefaultDocumentIdGenerator;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\String\Slugger\AsciiSlugger;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;

// Configure the Environment with all the CommonMark and GFM parsers/renderers
$environment = new Environment([]);
$environment->addExtension(new TableExtension());
$environment->addExtension(new TaskListExtension());
$environment->addExtension(new AutolinkExtension());
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new GithubFlavoredMarkdownExtension());
$environment->addExtension(new FrontMatterExtension());

$converter = new MarkdownConverter($environment);

// Filesystem adapter
$adapter = new LocalFilesystemAdapter(__DIR__ . '/storage');

return [

    // Default format, when no file format is specified (json|html)
    'defaultFormat' => \Unscrew\Unscrew::FORMAT_JSON,

    // Filesystem to serve
    'filesystem'    => new Filesystem($adapter),

    // The parser to use to transform from Markdown to JSON;
    'parserToJson'  => new DefaultParser($converter),

    // Parser for transforming Markdown to HTML (optional)
    'parserToHtml'  => $converter,

    // Helper for identifying documents (optional)
    'documentIdGenerator' => new DefaultDocumentIdGenerator(new AsciiSlugger(), ''),
];
