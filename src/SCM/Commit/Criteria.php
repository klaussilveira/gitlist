<?php

declare(strict_types=1);

namespace GitList\SCM\Commit;

use DateTimeInterface;

class Criteria
{
    protected ?DateTimeInterface $from = null;
    protected ?DateTimeInterface $to = null;
    protected ?string $author = null;
    protected ?string $message = null;
    protected ?int $limit = null;

    public function getFrom(): ?DateTimeInterface
    {
        return $this->from;
    }

    public function setFrom(?DateTimeInterface $from): void
    {
        $this->from = $from;
    }

    public function getTo(): ?DateTimeInterface
    {
        return $this->to;
    }

    public function setTo(?DateTimeInterface $to): void
    {
        $this->to = $to;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }
}
