<?php

declare(strict_types=1);

namespace GitList\SCM\Exception;

use RuntimeException;

class CommandException extends RuntimeException
{
    public function isNotFoundException(): bool
    {
        return str_contains($this->message, 'does not exist')
            || str_contains($this->message, 'bad object')
            || str_contains($this->message, 'unknown revision');
    }
}
