<?php
/**
 * Unscrew test configuration file;
 */

use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\Flysystem\Filesystem;
use Unscrew\Parser\DefaultParser;
use Unscrew\DefaultDocumentIdGenerator;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use Symfony\Component\String\Slugger\AsciiSlugger;

// Create converter with minimum config
$environment = (new Environment([]))->addExtension(new CommonMarkCoreExtension());
$converter   = new MarkdownConverter($environment);

// Filesystem adapter
$adapter = new \League\Flysystem\InMemory\InMemoryFilesystemAdapter();

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
