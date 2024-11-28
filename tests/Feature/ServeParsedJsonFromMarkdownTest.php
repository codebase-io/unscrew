<?php

use Tests\UnscrewTestDouble;

describe('parses json from structured markdown', function () {
    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    it(  'parses json with default parser', function () {
        $config     = get_config();
        $filesystem = $config->getFilesystem();
        $filesystem->write('/test/structured.md', structured_markdown());
        $idGen   = new \Unscrew\FlysystemChecksumIdGenerator($filesystem);

        $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
        $unscrew->setDocumentIdGenerator($idGen);
        $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

        $request  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test/structured');
        $response = $unscrew->process($config->getFilesystem(), $request);
        $json     = \Safe\json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY);

        expect( $response->getStatusCode() )
            ->toBe( 200 )
            ->and($response->headers->get('Content-Type'))
            ->toBe('application/json')
            ->and($response->getContent())
            ->toBeJson()
            ->and($json['_docID'])
            ->toBe('74f66beb4b9edf068f8ef167e00de9ff')
            ->and($json['feature_image'])
            ->toBe('/testing/assets/sample.png')
            ->and($json['categories'])
            ->toBeArray()
            ->and($json['title'])
            ->toBe('How to properly test if Markdown is parsed to JSON')
            ->and($json['pages'])
            ->toBeArray()
            ->and($json['sessions'])
            ->toBeArray();
    });

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    it( 'throws error on missing end line', function () {
        $config     = get_config();
        $filesystem = $config->getFilesystem();
        $filesystem->write('/test/invalid.md', "# Some markdown, but no end line");
        $idGen   = new \Unscrew\FlysystemChecksumIdGenerator($filesystem);

        $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
        $unscrew->setDocumentIdGenerator($idGen);
        $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

        $request  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test/invalid');
        $response = $unscrew->process($config->getFilesystem(), $request);
        $json     = \Safe\json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY);

        expect( $response->getStatusCode() )
            ->toBe( 500 )
            ->and($json['error'])
            ->toContain('does not have a last line')
            ->and($json['path'])
            ->toContain('invalid');
    });
});
