# Unscrew 

*Markdown-to-JSON headless CMS*

Built atop symfony/http, league/commonmark and league/flysystem, Unscrew 
is your bare-bones JSON API in front of your .md files.

## Reasons
**The best reason** probably is that you can organize the content any way you like, and store it independently of
the publishing platform.

Our team agrees - it's easier to manage, store and edit content in Markdown. It's a simple format
that doesn't require a lot of formatting and also support all of the features you'll need to publish content. 

Also decoupling the content with images/videos from the database is generally a good idea. You can store your 
content anywhere and rebuild it on your website without worrying about access roles the /admin. 
Our projects using this approach, don't have an admin dashboard at all.

## Parsing

Parsing markdown it usually done to HTML. However, when it comes to JSON there's a much higher coupling 
with the code consuming it. So don't settle for the default collection of parsers, and don't hesitate 
to implement your own.

The parser's interface only requirement is the `parse(string $filename)` method: 

```php
interface ParserInterface
{
    public function parse(string $filename): array;
}
```

Check the list of available parsers (// TODO)

## Installation

```shell
composer create-project unscrew/unscrew
```

## Usage
