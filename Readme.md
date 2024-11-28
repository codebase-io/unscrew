# Unscrew 

*Markdown-to-JSON headless CMS*

Built atop **symfony/http**, **league/commonmark** and **league/flysystem**, Unscrew 
is your bare-bones hackable JSON API in front of your Markdown files. 
Although it's main purpose is to expose JSON, you thanks to the powerful commonmark library, 
you can serve HTML too.

## Reasons
**The best reason** probably is that you can decouple the content from the publishing platform.

## Installation

```shell
composer create-project unscrew/project unscrew
cd unscrew
docker compose up -d
```

Open http://localhost:1888/ .
The default composer stack uses Apache, with the config file located under `public/apache2.conf`. 

## Configuration

The configuration options are stored in `config.php`. Here you'll see we create a 
commonmark parser object and filesystem adapter to be used when parsing and locating files.
You can use any of the flysytem adapters (s3/ftp/box/azure/dropbox): 

 - defaultFormat: the format to parse Markdown files to. `json`, `html` or `md`.

## Routing

## Customization

## Parsing

Parsing markdown it usually done to HTML. However, when it comes to JSON there's a much higher coupling
with the code consuming it. So don't settle for the default collection of parsers, and don't hesitate
to implement your own.

The parser's interface only requirement is the `parse(string $filename)` method:

```php
interface ParserInterface
{
    public function parse(stream, ?string $documentRoot=NULL, ?string $documentId=NULL): array;
}
```

Check the list of available parsers (// TODO)
