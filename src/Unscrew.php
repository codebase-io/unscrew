<?php

namespace Unscrew;


use Throwable;
use InvalidArgumentException;
use Unscrew\Parser\ParserInterface;
use Symfony\Component\Mime\MimeTypes;
use League\Flysystem\FilesystemOperator;
use League\CommonMark\ConverterInterface;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Unscrew
{
    const DEFAULT_HTML_TPL = <<<HTML
        <html>
            <head>
                <title>{title}</title>
                <style media="all">
                    body{
                        background: #fdf6e3;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
                    }
                    img { max-width: 100%; max-height: 100%; display: block; }
                    article { width: 71vw; background: #eee8d5; padding: 24px; border-radius: 3px; }
                </style>
                <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/styles/default.min.css">
                <script src="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/highlight.min.js"></script>
            </head>
            <body>
                <article>{content}</article>
                <script>hljs.highlightAll();</script>
            </body>
        </html>
    HTML;

    const FORMAT_JSON = 'json';
    const FORMAT_HTML = 'html';

    private array $markdownExtensions = ['md', 'markdown', 'mkd', 'mdown', 'mdwn', 'mdtxt', 'mdtext'];

    private MimeTypes $mimeTypes;

    use RegisterParser;
    use ParseDocument;
    use ServeStatic;

    private function __construct(
        private readonly ParserInterface $parser,
        private string $defaultFormat = self::FORMAT_JSON,
        private ?ConverterInterface $markdownConverter = NULL,
        private ?DocumentIdGenerator $idGenerator = NULL,
        // TODO support custom html template
        private string $htmlTemplate = self::DEFAULT_HTML_TPL,
    ) {
        $this->mimeTypes = new MimeTypes();
    }

    public static function withParser(ParserInterface $parser): static
    {
        return new static($parser);
    }

    public function setDefaultFormat(string $format): void
    {
        if (!in_array($format, ['md', 'html', 'json'])) {
            // TODO is MD really supported as default?
            throw new InvalidArgumentException(sprintf("Format %s not supported as default.", $format));
        }

        $this->defaultFormat = $format;
    }

    public function setDocumentIdGenerator(DocumentIdGenerator $generator): void
    {
        $this->idGenerator = $generator;
    }

    public function setMarkdownHtmlConverter(ConverterInterface $converter): void
    {
        $this->markdownConverter = $converter;
    }

    public function setHtmlTemplate(string $template): void
    {
        $this->htmlTemplate= $template;
    }

    private function fileIsMarkdown(string $extension): bool
    {
        return in_array(strtolower($extension), $this->markdownExtensions);
    }

    private function getRouteFilenameVariants(string $pathinfo, ?string $ext): iterable
    {
        $pathinfo = $pathinfo === DIRECTORY_SEPARATOR ? "" : $pathinfo;
        $bpath    = mb_substr($pathinfo, 0, mb_strrpos($pathinfo, '.'));

        yield $pathinfo;

        foreach ($this->markdownExtensions as $mdext) {
            $bpath && yield "{$bpath}.{$mdext}";
            yield "{$pathinfo}.{$mdext}";
            !$bpath && yield $pathinfo . DIRECTORY_SEPARATOR . "index.{$mdext}";
        }
    }

    /**
     * Given request path, resolves filename on filesystem
     * @throws FilesystemException
     */
    private function getFilenameByPath(
        FilesystemOperator $filesystem,
        string $pathinfo,
        ?string $ext='md'
    ): string
    {
        foreach ($this->getRouteFilenameVariants($pathinfo, $ext) as $path) {
            if ($filesystem->fileExists($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException(sprintf("404 - File not found for path %s.", $pathinfo));
    }

    private function respondWithError(
        array $info,
        string $format=self::FORMAT_JSON,
        int $status=400,
    ): Response
    {
        if (self::FORMAT_JSON === $format) {
            return new JsonResponse($info, $status);
        }

        $title = sprintf("<title>%s</title>", $info['error'] ?? "Unknown error");
        $body = sprintf(
            <<<HTML
                <body style='text-align: center; font-family: monospace;'>
                    <h1>404</h1>
                    <p>Could not find a Markdown document matching path `%s`.</p>
                </body>
            HTML,
                $info['path'] ?? "",
        );

        return new Response("<html lang='en'>{$title} {$body}</html>", $status);
    }

    /**
     * @throws FilesystemException
     */
    protected function process(FilesystemOperator $filesystem, Request $request): Response
    {
        $rqpath = $request->getPathInfo();
        $path   = (DIRECTORY_SEPARATOR . trim($rqpath, '/'));

        $fmt    = str_contains($rqpath, '.') ? mb_substr($rqpath, mb_strripos($rqpath, '.')+1) : NULL;
        $format = $fmt ?? $this->defaultFormat;

        if (!$filesystem->directoryExists('/')) {
            throw new InvalidArgumentException("Path to serve should be a directory and should be readable.");
        }

        try {
            $filename = $this->getFilenameByPath($filesystem, $path, $fmt);
            $ext      = mb_substr($filename, mb_strripos($filename, '.')+1);

            if ($this->fileIsMarkdown($ext) and $this->canParseTo($format)) {
                // Parse Markdown to format
                $document = new Document($filename, dirname($filename), $this->idGenerator?->generate($request, $filename));
                return $this->parseDocument(
                    $document,
                    $filesystem,
                    $format,
                );
            }

            // Serve static resource
            return $this->serveStatic(
                $filesystem,
                $filename,
                $filesystem->mimeType($filename),
            );
        }
        catch (InvalidArgumentException $exception) {
            $status = Response::HTTP_NOT_FOUND;
        }
        catch ( Throwable $exception) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        // Error
        return $this->respondWithError([
            'document'=> $filename ?? NULL,
            'path'    => $request->getPathInfo(),
            'error'   => $exception?->getMessage(),
        ], $format, $status);
    }

    /**
     * @throws FilesystemException
     * @codeCoverageIgnore
     */
    public function serve(FilesystemOperator $filesystem, Request $request): void
    {
        $this->process($filesystem, $request)->send();
    }
}
