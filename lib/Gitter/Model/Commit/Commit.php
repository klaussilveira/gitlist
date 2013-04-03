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
    protected $body;
    protected $diffs;

    public function importData(array $data)
    {
        $this->setHash($data['hash']);
        $this->setShortHash($data['short_hash']);
        $this->setTreeHash($data['tree']);
        $this->setParentsHash(isset($data['parents']) ? array_filter(explode(' ', $data['parents'])) : array());

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

        if (isset($data['body'])) {
            $this->setBody($data['body']);
        }
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    public function getShortHash()
    {
        return $this->shortHash;
    }

    public function setShortHash($shortHash)
    {
        $this->shortHash = $shortHash;
        return $this;
    }

    public function getTreeHash()
    {
        return $this->treeHash;
    }

    public function setTreeHash($treeHash)
    {
        $this->treeHash = $treeHash;
        return $this;
    }

    public function getParentsHash()
    {
        return $this->parentsHash;
    }

    public function setParentsHash($parentsHash)
    {
        $this->parentsHash = $parentsHash;
        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    public function getCommiter()
    {
        return $this->commiter;
    }

    public function setCommiter($commiter)
    {
        $this->commiter = $commiter;
        return $this;
    }

    public function getCommiterDate()
    {
        return $this->commiterDate;
    }

    public function setCommiterDate($commiterDate)
    {
        $this->commiterDate = $commiterDate;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }
    
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function getDiffs()
    {
        return $this->diffs;
    }

    public function setDiffs($diffs)
    {
        $this->diffs = $diffs;
        return $this;
    }

    public function getChangedFiles()
    {
        return sizeof($this->diffs);
    }
}

