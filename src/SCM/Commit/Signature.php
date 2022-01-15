<?php

declare(strict_types=1);

namespace GitList\SCM\Commit;

class Signature
{
    protected bool $valid = false;

    public function __construct(protected string $signer, protected string $key)
    {
    }

    public function getSigner(): string
    {
        return $this->signer;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function validate(): void
    {
        $this->valid = true;
    }
}
