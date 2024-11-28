<?php

namespace Unscrew;

use League\CommonMark\ConverterInterface;
use League\Flysystem\FilesystemOperator;
use Unscrew\Parser\ParserInterface;

class Config
{
    public function __construct(
        private FilesystemOperator $filesystem,
        private ParserInterface $parserToJson,
        private ?ConverterInterface $parserToHtml = NULL,
        private ?DocumentIdGenerator $documentIdGenerator = NULL,
        private string $defaultFormat = Unscrew::FORMAT_JSON,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            $config['filesystem'],
            $config['parserToJson'],
            $config['parserToHtml'] ?? NULL,
            $config['documentIdGenerator'] ?? NULL,
            $config['defaultFormat'] ?? Unscrew::FORMAT_JSON,
        );
    }

    public function getFilesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function getParserToJson(): ParserInterface
    {
        return $this->parserToJson;
    }

    public function getParserToHtml(): ?ConverterInterface
    {
        return $this->parserToHtml;
    }

    public function getDocumentIdGenerator(): ?DocumentIdGenerator
    {
        return $this->documentIdGenerator;
    }

    public function getDefaultFormat(): string
    {
        return $this->defaultFormat;
    }
}
