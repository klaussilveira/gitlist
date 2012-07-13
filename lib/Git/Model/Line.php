<?php

namespace Git\Model;

class Line
{
    protected $line;
    protected $type;
    protected $numOld;
    protected $numNew;

    public function __construct($data, $numOld, $numNew)
    {
        if (!empty($data)) {
            switch ($data[0]) {
                case '@':
                    $this->setType('chunk');
                    $this->setNumNew('...');
                    $this->setNumOld('...');
                    break;
                case '-':
                    $this->setType('old');
                    $this->setNumOld($numOld);
                    $this->setNumNew('');
                    break;
                case '+':
                    $this->setType('new');
                    $this->setNumNew($numNew);
                    $this->setNumOld('');
                    break;
                default:
                    $this->setNumOld($numOld);
                    $this->setNumNew($numNew);
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
    
    public function getNumOld()
    {
        return $this->numOld;
    }

    public function setNumOld($num)
    {
        $this->numOld = $num;
    }
    
    public function getNumNew()
    {
        return $this->numNew;
    }

    public function setNumNew($num)
    {
        $this->numNew = $num;
    }
}
