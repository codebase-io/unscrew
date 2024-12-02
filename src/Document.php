<?php

namespace Unscrew;

class Document
{
    public function __construct(
        private readonly string $filename,
        private readonly ?string $root,
        private readonly ?string $id,
    ) {}

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getRoot(): ?string
    {
        return $this->root;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
