<?php

declare(strict_types=1);

namespace GitList\SCM\Commit;

class Signature
{
    public const STATUS_GOOD = 'G';
    public const STATUS_BAD = 'B';
    public const STATUS_UNKNOWN = 'U';

    public function __construct(protected string $signer, protected string $key, protected string $status = self::STATUS_UNKNOWN)
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isValid(): bool
    {
        return self::STATUS_GOOD === $this->status;
    }

    public function isBad(): bool
    {
        return self::STATUS_BAD === $this->status;
    }
}
