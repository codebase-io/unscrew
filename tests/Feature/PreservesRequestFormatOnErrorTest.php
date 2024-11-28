<?php

use Tests\UnscrewTestDouble;

describe('preserves format on error', function () {
    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    it( 'preserves json format on parsing error', function () {
        $config     = get_config();
        $filesystem = $config->getFilesystem();
        $filesystem->write('/test/invalid.md', "# Some markdown, but no end line");
        $idGen   = new \Unscrew\FlysystemChecksumIdGenerator($filesystem);

        $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
        $unscrew->setDocumentIdGenerator($idGen);
        $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

        $request  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test/invalid.json');
        $response = $unscrew->process($config->getFilesystem(), $request);
        $json     = \Safe\json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY);

        expect( $response->getStatusCode() )
            ->toBe( 500 )
            ->and($json['error'])
            ->toContain('does not have a last line')
            ->and($json['path'])
            ->toContain('invalid');
    });

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    it( 'preserves html format 404 error', function () {
        $config     = get_config();

        $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
        $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

        $request  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/test/notfound.html');
        $response = $unscrew->process($config->getFilesystem(), $request);
        $html = $response->getContent();

        expect($response->getStatusCode())
            ->toBe(404)
            ->and($html)
            ->toContain('not found for path');
    });
});
