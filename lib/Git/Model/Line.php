<?php

namespace Git\Model;

class Line
{
    protected $line;
    protected $type;

    public function __construct($data)
    {
        if (!empty($data)) {
            if ($data[0] == '@') {
                $this->setType('chunk');
            }

            if ($data[0] == '-') {
                $this->setType('old');
            }

            if ($data[0] == '+') {
                $this->setType('new');
            }
        }

        $this->setLine($data);
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setLine($line)
    {
        $this->line = $line;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
}