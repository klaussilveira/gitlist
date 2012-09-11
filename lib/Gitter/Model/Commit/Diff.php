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

use Gitter\Model\AbstractModel;

class Diff extends AbstractModel
{
    protected $lines;
    protected $index;
    protected $old;
    protected $new;
    protected $file;

    public function addLine($line, $oldNo, $newNo)
    {
        $this->lines[] = new DiffLine($line, $oldNo, $newNo);
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setOld($old)
    {
        $this->old = $old;
    }

    public function getOld()
    {
        return $this->old;
    }

    public function setNew($new)
    {
        $this->new = $new;
    }

    public function getNew()
    {
        return $this->new;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }
}
