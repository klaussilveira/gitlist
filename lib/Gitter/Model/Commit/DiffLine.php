<?php

/*
 * This file is part of the Gitter library.
 *
 * (c) Klaus Silveira <klaussilveira@php.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gitter\Model\Commit;

use Gitter\Model\Line;

class DiffLine extends Line
{
    protected $numNew;
    protected $numOld;

    public function __construct($data, $numOld, $numNew)
    {
        parent::__construct($data);

        if (!empty($data)) {
            switch ($data[0]) {
                case '@':
                    $this->numOld = '...';
                    $this->numNew = '...';
                    break;
                case '-':
                    $this->numOld = $numOld;
                    $this->numNew = '';
                    break;
                case '+':
                    $this->numOld = '';
                    $this->numNew = $numNew;
                    break;
                default:
                    $this->numOld = $numOld;
                    $this->numNew = $numNew;
            }
        } else {
            $this->numOld = $numOld;
            $this->numNew = $numNew;
        }
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
