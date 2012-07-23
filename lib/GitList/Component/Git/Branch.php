<?php

namespace GitList\Component\Git;

class Branch
{
    protected $ahead = 0;
    protected $behind = 0;
    protected $current = false;
    protected $hash = '';
    protected $lastMsg = '';
    protected $name = '';
    protected $raw = '';

    public function __construct($line)
    {
        $this->parseLine($line);
    }

    protected function parseLine($line)
    {
        $m = array();
        if (!preg_match('/^(\*?)\s+(\S+)\s+([0-9a-f]{7})\s+(.+)$/', $line, $m)) {
            throw new \Exception('Invalid branch line.');
        }

        if ('*' == $m[1]) {
            $this->current = true;
        }

        $this->name = $m[2];
        $this->hash = $m[3];
        $this->lastMsg = $m[4];

        $m = array();
        if (preg_match('/\[ahead\s+(\d+)\]\s+(.+)/', $this->lastMsg, $m)) {
            $this->ahead = $m[1];
            $this->lastMsg = $m[2];
        }

        $m = array();
        if (preg_match('/\[behind\s+(\d+)\]\s+(.+)/', $this->lastMsg, $m)) {
            $this->behind = $m[1];
            $this->lastMsg = $m[2];
        }

        $this->raw = $line;
    }

    public function getAhead()
    {
        return $this->ahead;
    }

    public function getBehind()
    {
        return $this->behind;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getLastMsg()
    {
        return $this->lastMsg;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function isCurrent()
    {
        return $this->current;
    }
}
