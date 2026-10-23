<?php

declare(strict_types=1);

namespace GitList\SCM;

class Submodule extends Item
{
    public function __construct(Repository $repository, string $hash, protected string $name, protected string $url = '')
    {
        parent::__construct($repository, $hash, substr($hash, 0, 7));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFileName(): string
    {
        return basename($this->name);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isBrowsable(): bool
    {
        return 1 === preg_match('#^https?://#i', $this->url);
    }
}
