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
use Gitter\Util\DateTime;

class Commit extends AbstractModel
{
    protected $hash;
    protected $shortHash;
    protected $treeHash;
    protected $parentsHash;
    protected $author;
    protected $date;
    protected $commiter;
    protected $commiterDate;
    protected $message;
    protected $diffs;

    public function importData(array $data)
    {
        $this->setHash($data['hash']);
        $this->setShortHash($data['short_hash']);
        $this->setTreeHash($data['tree']);
        $this->setParentsHash(array_filter(explode(' ', $data['parents'])));

        $this->setAuthor(
            new Author($data['author'], $data['author_email'])
        );

        $this->setDate(
            new DateTime('@' . $data['date'])
        );

        $this->setCommiter(
            new Author($data['commiter'], $data['commiter_email'])
        );

        $this->setCommiterDate(
            new DateTime('@' . $data['commiter_date'])
        );

        $this->setMessage($data['message']);
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    public function getShortHash()
    {
        return $this->shortHash;
    }

    public function setShortHash($shortHash)
    {
        $this->shortHash = $shortHash;
    }

    public function getTreeHash()
    {
        return $this->treeHash;
    }

    public function setTreeHash($treeHash)
    {
        $this->treeHash = $treeHash;
    }

    public function getParentsHash()
    {
        return $this->parentsHash;
    }

    public function setParentsHash($parentsHash)
    {
        $this->parentsHash = $parentsHash;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getCommiter()
    {
        return $this->commiter;
    }

    public function setCommiter($commiter)
    {
        $this->commiter = $commiter;
    }

    public function getCommiterDate()
    {
        return $this->commiterDate;
    }

    public function setCommiterDate($commiterDate)
    {
        $this->commiterDate = $commiterDate;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getDiffs()
    {
        return $this->diffs;
    }

    public function setDiffs($diffs)
    {
        $this->diffs = $diffs;
    }

    public function getChangedFiles()
    {
        return sizeof($this->diffs);
    }
}
