<?php

use Tests\UnscrewTestDouble;
use League\Flysystem\FilesystemException;


/**
 * @throws FilesystemException|\Safe\Exceptions\JsonException
 */
test('serve html from markdown', function () {
    $config = get_config();
    $filesystem = $config->getFilesystem();
    $filesystem->write('/test.md', some_markdown());

    $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
    $unscrew->setDocumentIdGenerator($config->getDocumentIdGenerator());
    $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

    $request  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test.html');
    $response = $unscrew->process($config->getFilesystem(), $request);

    expect( $response->getStatusCode() )
        ->toBe( 200 )
        ->and($response->headers->get('Content-Type'))
        ->toBe('text/html')
        ->and( $response->getContent() )
        ->toBeString()
        ->toContain( 'This file is served from the memory storage' )
        ->toContain( '<title>test.md</title>' )
        ->toContain( '<body>' )
        ->toContain( '<html>' );

});
