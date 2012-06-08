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
        if (empty($data)) {
            throw new \RuntimeException('Error: could not import commit data.');
        }
	
        if (!isset($data['hash'])) {
            throw new \RuntimeException('Error: could not import commit hash data.');
        }
        else {
            $this->setHash($data['hash']);
        }

        if (!isset($data['short_hash'])) {
            throw new \RuntimeException('Error: could not import commit hash data.');
        }
        else {
            $this->setShortHash($data['short_hash']);
        }

        if (!isset($data['tree'])) {
            throw new \RuntimeException('Error: could not import commit tree data.');
        }
        else {
            $this->setTreeHash($data['tree']);
        }

        if (!isset($data['parent'])) {
            throw new \RuntimeException('Error: could not import commit parent data.');
        }
        else {
            $this->setParentHash($data['parent']);
        }

        if (!isset($data['author'])) {
            throw new \RuntimeException('Error: could not import commit author data.');
        }
        elseif (!isset($data['author_email'])) {
            throw new \RuntimeException('Error: could not import commit author email data.');
        }
        else {
            $this->setAuthor(
                new Author($data['author'], $data['author_email'])
            );
        }

        if (!isset($data['date'])) {
            throw new \RuntimeException('Error: could not import commit date data.');
        }
        else {
	    $this->setDate(
                new \DateTime('@' . $data['date'])
            );
        }

        if (!isset($data['commiter_email'])) {
            throw new \RuntimeException('Error: could not import commit commiter email data.');
        }
        else {
            $this->setCommiter(
                new Author($data['commiter'], $data['commiter_email'])
            );
        }

        if (!isset($data['commiter_date'])) {
            throw new \RuntimeException('Error: could not import commit commiter date data.');
        }
        else {
            $this->setCommiterDate(
                new \DateTime('@' . $data['commiter_date'])
            );
        }

        if (!isset($data['message'])) {
            throw new \RuntimeException('Error: could not import commit commiter date data.');
        }
        else {
            $this->setMessage($data['message']);
        }
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
