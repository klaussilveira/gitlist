<?php

namespace GitList\Component\Git\Model;

use GitList\Component\Git\Client;
use GitList\Component\Git\Repository;
use GitList\Component\Git\ScopeAware;

class Tree extends ScopeAware implements \RecursiveIterator
{
    protected $mode;
    protected $hash;
    protected $name;
    protected $age;
    protected $path;
    protected $data;
    protected $position = 0;

    public function __construct($hash, Client $client, Repository $repository)
    {
        $this->setClient($client);
        $this->setRepository($repository);
        $this->setHash($hash);
        $this->setPath($hash == "master" ? "" : preg_replace("/master.*\"(.*)\"\//", "$1", $hash));
    }

    public function parse()
    {
        $data = $this->getClient()->run($this->getRepository(), 'ls-tree -l ' . $this->getHash());
        $lines = explode("\n", $data);
        $files = array();
        $root = array();

        foreach ($lines as $key => $line) {
            if (empty($line)) {
                unset($lines[$key]);
                continue;
            }

            $files[] = preg_split("/[\s]+/", $line, 5);
        }

        foreach ($files as $file) {
            if ($file[1] == 'commit') {
                // submodule
                continue;
            }

            $filePath = $this->getPath() != "" ? $this->getPath() . "/$file[4]" : $file[4];
            $age = $this->getClient()->run($this->getRepository(), 'log -1 --date=relative --format="%ad" -- ' . "\"$filePath\"");
            $age = preg_replace("/(\d year.*),.*/", "$1 ago", $age);

            if ($file[0] == '120000') {
                $show = $this->getClient()->run($this->getRepository(), 'show ' . $file[2]);
                $tree = new Symlink;
                $tree->setMode($file[0]);
                $tree->setName($file[4]);
                $tree->setPath($show);
                $root[] = $tree;
                continue;
            }

            if ($file[1] == 'blob') {
                $blob = new Blob($file[2], $this->getClient(), $this->getRepository());
                $blob->setMode($file[0]);
                $blob->setName($file[4]);
                $blob->setAge($age);
                $blob->setSize($file[3]);
                $root[] = $blob;
                continue;
            }

            $tree = new Tree($file[2], $this->getClient(), $this->getRepository());
            $tree->setMode($file[0]);
            $tree->setName($file[4]);
            $tree->setAge($age);
            $root[] = $tree;
        }

        $this->data = $root;
    }

    public function output()
    {
        $files = $folders = array();

        foreach ($this as $node) {
            if ($node instanceof Blob) {
                $file['type'] = 'blob';
                $file['name'] = $node->getName();
                $file['age'] = $node->getAge();
                $file['size'] = $node->getSize();
                $file['mode'] = $node->getMode();
                $file['hash'] = $node->getHash();
                $files[] = $file;
                continue;
            }

            if ($node instanceof Tree) {
                $folder['type'] = 'folder';
                $folder['name'] = $node->getName();
                $folder['age'] = $node->getAge();
                $folder['size'] = '';
                $folder['mode'] = $node->getMode();
                $folder['hash'] = $node->getHash();
                $folders[] = $folder;
                continue;
            }

            if ($node instanceof Symlink) {
                $folder['type'] = 'symlink';
                $folder['name'] = $node->getName();
                $folder['size'] = '';
                $folder['mode'] = $node->getMode();
                $folder['hash'] = '';
                $folder['path'] = $node->getPath();
                $folders[] = $folder;
            }
        }

        // Little hack to make folders appear before files
        $files = array_merge($folders, $files);

        return $files;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    public function hasChildren()
    {
        return is_array($this->data[$this->position]);
    }

    public function next()
    {
        $this->position++;
    }

    public function current()
    {
        return $this->data[$this->position];
    }

    public function getChildren()
    {
        return $this->data[$this->position];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }
}
