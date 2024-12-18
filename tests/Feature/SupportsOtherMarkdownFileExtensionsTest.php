<?php

use Tests\UnscrewTestDouble;

describe('supports other markdown file extension', function () {
    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    it( 'supports .mdown and .markdown', function () {
        $config     = get_config();
        $filesystem = $config->getFilesystem();
        $filesystem->write('/markdown/test.mdown', some_markdown());
        $filesystem->write('/markdown/another/index.markdown', some_markdown());
        $idGen   = new \Unscrew\FlysystemChecksumIdGenerator($filesystem);

        $unscrew = UnscrewTestDouble::withParser($config->getParserToJson());
        $unscrew->setDocumentIdGenerator($idGen);
        $unscrew->setMarkdownHtmlConverter($config->getParserToHtml());

        // Serve test.mdown
        $requestA  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/markdown/test');
        $responseA = $unscrew->process($config->getFilesystem(), $requestA);
        $jsonOne   = \Safe\json_decode($responseA->getContent(), JSON_OBJECT_AS_ARRAY);

        // Serve index.markdown
        $requestB  = \Symfony\Component\HttpFoundation\Request::create('http://unscrew.cms/markdown/another/');
        $responseB = $unscrew->process($config->getFilesystem(), $requestB);
        $jsonTwo   = \Safe\json_decode($responseB->getContent(), JSON_OBJECT_AS_ARRAY);

        //var_dump($jsonTwo);

        expect( $responseA->getStatusCode() )
            ->toBe( 200 )
            ->and($jsonOne['_docID'])
            ->toBe('a8eab4f2173954bf412f9b65da4faa41')
            ->and($responseB->getStatusCode())
            ->toBe(200)
            ->and($jsonTwo['_docID'])
            ->toBe('a8eab4f2173954bf412f9b65da4faa41');
    });
});
