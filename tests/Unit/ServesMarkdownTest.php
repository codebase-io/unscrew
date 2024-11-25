<?php

use Tests\UnscrewTestDouble;
use League\Flysystem\FilesystemException;


/**
 * @throws FilesystemException|\Safe\Exceptions\JsonException|\Safe\Exceptions\OutcontrolException
 */
test('serve markdown', function () {
    $config    = get_config();
    $filesystem = $config->getFilesystem();
    $filesystem->write('/test.md', some_markdown());

    $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
    $unscrew->setDocumentIdGenerator($config->getDocumentIdGenerator());
    $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

    $request = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test.md');
    $response = $unscrew->process($config->getFilesystem(), $request);

    // Stream content
    \Safe\ob_start();
    $response->sendContent();
    $content = ob_get_contents();
    \Safe\ob_end_clean();

    expect( $response->getStatusCode() )
        ->toBe(200)
        ->and($content)
        ->toContain( '# Unscrew in-memory test file' )
        ->and($response->headers->get('Content-Type'))
        ->toBe('text/markdown');
});
