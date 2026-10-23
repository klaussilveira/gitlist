<?php

declare(strict_types=1);

namespace GitList\SCM;

class RemoteUrl
{
    public static function redact(string $url): string
    {
        return (string) preg_replace('#^(\[[^\]]*\])?([a-z][a-z0-9+.-]*://)[^/]*@#i', '$1$2', $url);
    }
}
