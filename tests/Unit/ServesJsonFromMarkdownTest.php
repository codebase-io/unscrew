<?php

use Tests\UnscrewTestDouble;
use League\Flysystem\FilesystemException;


/**
 * @throws FilesystemException|\Safe\Exceptions\JsonException
 */
test('serve json from markdown', function () {
    $config    = get_config();
    $filesystem = $config->getFilesystem();
    $filesystem->write('/test.md', some_markdown());

    $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
    $unscrew->setDocumentIdGenerator($config->getDocumentIdGenerator());
    $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

    $request = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test');
    $response = $unscrew->process($config->getFilesystem(), $request);
    $json     = \Safe\json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY);

    expect( $response->getStatusCode() )
        ->toBe( 200 )
        ->and($response->headers->get('Content-Type'))
        ->toBe('application/json')
        ->and( $json['_docID'] )
        ->toBe( 'test-md' )
        ->and( $json['_docRoot'] )
        ->toBe( 'http://unscrew.cms' )
        ->and( $json['title'] )
        ->toBe( 'Unscrew in-memory test file' )
        ->and( $json['description'] )
        ->toBeString();

});
