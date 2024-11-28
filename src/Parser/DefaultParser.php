<?php

namespace Unscrew\Parser;

use Exception;
use RuntimeException;
use DateTimeImmutable;
use InvalidArgumentException;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\Config\Exception\ConfigurationExceptionInterface;

/**
 * Implementation of a default parser;
 * See ../../storage folder for samples
 */
class DefaultParser implements ParserInterface
{
    // Maximum line length to parse; longer lines will be trimmed.
    const MAX_LINE_LENGTH = 10240;

    // Fields from front matter to convert to JSON bool
    private array $mapToBoolean = [
        'published',
    ];

    // Fields from front matter to convert to JSON date
    private array $mapToDate = [
        'publishDate',
    ];

    public function __construct(
        private readonly ConverterInterface $converter,
    ) {}

    /**
     * @throws Exception
     */
    private function parseMetaLine(string $line, DefaultParserDto $dto): void
    {
        if ($dto->metaSectionParsed) {
            throw new RuntimeException("Meta already processed. Parser does not support multiple metadata blocks.");
        }

        if ( str_starts_with( $line, '---' ) ) {
            $dto->metaSection       = !$dto->metaSection;    // 1. True , 2. False
            $dto->metaSectionParsed = !$dto->metaSection;    // 1. False, 2. True

            return;
        }

        $key = trim(substr($line, 0, strpos($line, ':')));
        $val = trim(substr($line, strpos($line, ':')+1));

        // Support lists separated by comma (,)
        if ( strpos( $val, ',' ) ) {
            $val = array_unique(array_map(fn(string $v)=> trim($v), explode(',', $val)));
        }

        // Conversions
        if (in_array($key, $this->mapToBoolean)) {
            $val = boolval($val);
        }

        if (in_array($key, $this->mapToDate)) {
            $val = (new DateTimeImmutable())->setTimestamp(\Safe\strtotime($val));
        }

        if ('author' == $key){
            // Parse author format
            $name = str_contains($val, '<') ? mb_substr($val, 0, mb_strpos($val, '<')) : $val;
            $email = str_contains($val, '<') ? mb_substr($val, mb_strpos($val, '<')+1, mb_stripos($val, '>')- mb_strlen($val)) : NULL;

            $val = ['name'=> trim($name), 'email'=> $email ? trim($email) : NULL];
        }

        $dto->addData($key, $val);
    }

    private function parseLink(string $line, DefaultParserDto $dto, bool $parseOnly=FALSE): array
    {
        if(!$dto->currSection || $parseOnly) {
            $line = trim($line);

            //Lookup supported links
            if (str_starts_with($line, '![')) {
                // Image
                $line = mb_substr($line, mb_strpos($line, '![')+2);
            }
            else{
                // Anchor
                $line = mb_substr($line, mb_strpos($line, '[')+1);
            }

            $name = mb_substr($line, 0, strpos($line, ']'));
            $link = trim(mb_substr($line, mb_strpos($line, '(')+1, mb_strripos($line, ')')-mb_strlen($line)));

            if (!$parseOnly) {
                $name = str_replace(' ', '-', mb_strtolower($name));
                $dto->addData($name, $link);
            }
        }

        return [$name ?? NULL, $link ?? NULL];
    }

    /**
     * @throws CommonMarkException|ConfigurationExceptionInterface
     */
    private function endSection(DefaultParserDto $dto): void
    {
        // Flush buffer on section change
        !empty($dto->buffer) && $dto->flushBufferTo($dto->currSection, $this->converter);
        // Flag section change line to ignore
        $dto->sectionChangeLine = $dto->lineNumber;

        // Check of pages content
        if (isset($dto->pageName) && isset($dto->pageContent[$dto->pageName])) {
            $content                          = $dto->pageContent[$dto->pageName] ?? NULL;
            $dto->pageContent[$dto->pageName] = $content ? $this->converter->convert($content)->getContent() : NULL;
            $dto->addData('pages', $dto->pageContent);

            $dto->pageName = NULL;
            $dto->pageContent = [];
        }

        // Check of sessions content
        if (isset($dto->chapterName) && isset($dto->chaptersContent[$dto->chapterName])) {
            $dto->addData('sessions', $dto->chaptersContent);

            $dto->chaptersContent = [];
            $dto->chapterName     = NULL;
        }
    }

    /**
     * @throws CommonMarkException|ConfigurationExceptionInterface
     */
    private function parseHeadingLine(string $line, DefaultParserDto $dto): void
    {
        $headingLvl = substr_count(substr($line, 0, 6), '#');
        $heading    = trim(trim($line, '#'));
        $section    = $dto->currSection;

        // Section flag
        if ('__lessons__' == strtolower($heading) or '__sessions__' == strtolower($heading)) {
            $section = 'sessions';
        }

        if ('__pages__' == strtolower($heading) or '__sections__' == strtolower($heading))
        {
            $section = 'pages';
        }

        if ('__description__' == strtolower($heading) or '__intro__' == strtolower($heading))
        {
            $section = 'description';
        }

        if ($section) {
            ($section != $dto->currSection) && $this->endSection($dto);
            $dto->currSection = $section;

            // Handled by parseSection
            return;
        }

        if (1 == $headingLvl and !$dto->titleParsed) {
            $dto->titleParsed = !$dto->titleParsed;
            $dto->addData('title', $heading);
            return;
        }

        if (2 == $headingLvl and !$dto->headingTitleParsed) {

            $dto->headingTitleParsed = !$dto->headingTitleParsed;
            $dto->addData('headingTitle', $heading);
            return;
        }

        if (3 == $headingLvl and !$dto->subHeadingTitleParsed) {
            $dto->subHeadingTitleParsed = !$dto->subHeadingTitleParsed;
            $dto->addData('subHeadingTitle', $heading);
            return;
        }

        throw new InvalidArgumentException("Error on line {$dto->lineNumber}: Heading line {$line} not matched by the parser.");
    }

    /**
     * @throws CommonMarkException|ConfigurationExceptionInterface
     */
    private function parseSectionLine(string $line, DefaultParserDto $dto): void
    {
        if ($dto->lineNumber == $dto->sectionChangeLine) {
            // Do not parse section title line
            return;
        }

        if ('description' == $dto->currSection) {
            // Description does not support html tags
            $dto->description.= trim(strip_tags($line));
            $dto->addData('description', $dto->description);
            return;
        }

        if ('pages' == $dto->currSection) {
            if (str_starts_with($line, '#')) {
                // Page definition
                $pgName = strtolower(trim(trim($line, '#')));

                if (isset($dto->pageName) && $pgName != $dto->pageName) {
                    // New page, flush content for previous page
                    $content                          = $dto->pageContent[$dto->pageName] ?? NULL;
                    $dto->pageContent[$dto->pageName] = $content ? $this->converter->convert($content)->getContent() : NULL;
                    $dto->addData('pages', $dto->pageContent);
                }

                $dto->pageName = $pgName;
                return;
            }

            // Append lines
            if (isset($dto->pageName)) {
                $dto->pageContent[$dto->pageName] = $dto->pageContent[$dto->pageName] ?? '';
                $dto->pageContent[$dto->pageName].= "\n{$line}";
            }
        }

        // Lessons / Sessions
        if ('sessions' == $dto->currSection) {
            // Parse chapters
            if (str_starts_with($line, '#')) {
                $chptrName = strtolower( trim( trim( $line, '#' ) ) );

                if (isset($dto->chapterName) && $chptrName != $dto->chapterName) {
                    // New chapter, flush content for previous chapter
                    $dto->addData('sessions', $dto->chaptersContent);
                }
                $dto->chapterName = $chptrName;
                return;
            }

            if ($dto->chapterName){
                list($title, $link) = $this->parseLink($line, $dto, TRUE);
                mb_strlen($title) && $dto->chaptersContent[$dto->chapterName][$title] = $link;
            }
        }
    }

    /**
     * @throws CommonMarkException|ConfigurationExceptionInterface
     */
    private function parseLastLine(string $line, DefaultParserDto $dto): void
    {
        $line = trim(mb_strtolower($line));

        if ('[//]: # (end)' === $line) {
            // Last line
            $dto->lastLine          = $dto->lineNumber;
            $dto->currSection       = '___end___';

            $this->endSection($dto);
        }
    }

    /**
     * Parse markdown file to json
     *
     * @param resource $stream
     * @param string|null $documentRoot
     * @param string|null $documentId
     *
     * @return array
     * @throws CommonMarkException
     * @throws ConfigurationExceptionInterface
     * @throws Exception
     */
    public function parse(
        $stream,
        ?string $documentRoot=NULL,
        ?string $documentId=NULL
    ): array
    {
        $dto = new DefaultParserDto();

        // Set document id
        $documentId && $dto->addData('_docID', $documentId);
        // Set assets root
        $documentRoot && $dto->addData('_docRoot', $documentRoot);

        // Read and process each line from stream
        while ( FALSE != ($line = fgets($stream, self::MAX_LINE_LENGTH)) ) {
            $line = trim($line);
            $dto->lineNumber++;

            if (empty($line)) {
                continue;
            }

            // Test for last line of the document
            $this->parseLastLine($line, $dto);

            if (isset($dto->lastLine) && $dto->lastLine) {
                // No more parsing after last line
                break;
            }

            // Parsing meta section ---
            if ( str_starts_with( $line, '---' ) or $dto->metaSection ) {
                $this->parseMetaLine($line, $dto);
                continue;
            }

            // Parse link/image outside of section
            if (!$dto->currSection && str_starts_with($line, '![') or str_starts_with($line, '[')) {
                $this->parseLink($line, $dto);
                continue;
            }

            // Parse headings
            if (str_starts_with($line, '#')) {
                $this->parseHeadingLine($line, $dto);
            }

            // Parse section line
            $this->parseSectionLine($line, $dto);

        }

        if (!isset($dto->lastLine) || !$dto->lastLine) {
            // Last line is mandatory
            throw new RuntimeException("File does not have a last line. Add `[//]: # (end)` to indicate the last line of the document.");
        }

        return $dto->jsonSerialize();
    }
}
