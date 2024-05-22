<?php

namespace Unscrew\Parser;

use JsonSerializable;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

class DefaultParserDto implements JsonSerializable
{
    public string $buffer = '';
    public int $lineNumber = 0;
    public int $sectionChangeLine = 0;
    public bool $metaSection = FALSE;
    public bool $metaSectionParsed = FALSE;
    public bool $titleParsed = FALSE;
    public bool $headingTitleParsed = FALSE;
    public bool $subHeadingTitleParsed = FALSE;

    // Flag for current section
    public ?string $currSection = NULL;

    public ?string $description = '';

    public array $pageContent = [];
    public ?string $pageName;

    public array $chaptersContent = [];
    public ?string $chapterName;

    public int $lastLine;

    private array $data = [];

    public function addData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @throws CommonMarkException
     */
    public function flushBufferTo(string $section, CommonMarkConverter $converter): void
    {
        if (empty($this->buffer)) {
            return ;
        }

        $html = $converter->convert($this->buffer);
        $this->addData($section, $html->getContent());

        $this->buffer = NULL;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
