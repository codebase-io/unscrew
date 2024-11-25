<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

global $config;

function get_config(): \Unscrew\Config
{
    global $config;

    $config = $config ?? \Unscrew\Config::fromArray(require_once __DIR__ . '/config.test.php');

    return $config;
}

function some_markdown(): string {
    return <<<Markdown
# Unscrew in-memory test file

#### __Description__

This file is served from the memory storage.

[//]: # (end)
Markdown;
}

function structured_markdown(): string {
    return <<<Markdown
---
language: en
intro_video: https://www.youtube.com/shorts/Af7KSWgmHZU
feature_image: /testing/assets/sample.png
categories: php, sqlite, json, markdown, cms
icon: <i class="fa fa-graduation-cap"></i>
published: false
publishDate: 2024-05-22
author: Johanna Doe <johanna.doe@example.net>
---

# How to properly test if Markdown is parsed to JSON

## Using Unscrew as your CMS to write markdown and serve JSON

[Intro Video](https://www.youtube.com/watch?v=xEZLL00S7gk)

![Heading Image](tests/assets/image.png)

#### __Description__

Learning Linux is valuable for many IT professions. For system administrators, DevOps engineers, 
and backend developers, it enables efficient server management, automation of software development and 
deployment, and the development and management of server-side applications.

## __Pages__

### Overview

You can put here the content for this section, which may pan one or more paragraphs.
Links to required lessons are also a good idea.

```shell
sudo apt install unscrew/package
```

```php
<?php

// Request -> response example
\$request  = new \Symfony\Component\HttpFoundation\Request();
\$response = new \Symfony\Component\HttpFoundation\Response();
```

And images too
![Heading Image](samples/assets/image.jpg)

### References (optional)

- [[EN] Wikipedia](http://wikipedia.org/)
- [[RO] Google](http://google.ro/)

### Help (optional)

### Changelog (optional)

 - Upgraded symfony
 - Upgraded php
 - Upgraded cms

## __Lessons__

### Programming is fun

 - [Programming Is Cooked](https://www.youtube.com/watch?v=KuLUd1UIvVA)
 - [2024: Learn Vim](https://www.youtube.com/shorts/qFnVZtvylIU)
 - [Lesson number 3](https://www.youtube.com/shorts/zqTzW6wW1CI)

### Using a Markdown-to-JSON CMS

 - [How to lesson 1](https://www.youtube.com/shorts/qFnVZtvylIU)
 - [How to lesson 2](https://www.youtube.com/shorts/zqTzW6wW1CI)

[//]: # (end)
Markdown;
}
