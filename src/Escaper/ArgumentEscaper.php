<?php

namespace GitList\Escaper;

class ArgumentEscaper
{
    public function escape($argument)
    {
        if ($argument === null) {
            return null;
        }

        return escapeshellcmd($argument);
    }
}
