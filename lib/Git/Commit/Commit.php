<?php

namespace Git\Commit;

class Commit
{
    protected $hash;
    protected $shortHash;
    protected $treeHash;
    protected $parentHash;
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
        $this->setParentHash($data['parent']);

        $this->setAuthor(
            new Author($data['author'], $data['author_email'])
        );

        $this->setDate(
            new \DateTime('@' . $data['date'])
        );

        $this->setCommiter(
            new Author($data['commiter'], $data['commiter_email'])
        );

        $this->setCommiterDate(
            new \DateTime('@' . $data['commiter_date'])
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

    public function getParentHash()
    {
        return $this->parentHash;
    }

    public function setParentHash($parentHash)
    {
        $this->parentHash = $parentHash;
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