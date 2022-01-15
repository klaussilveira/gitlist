<?php

declare(strict_types=1);

namespace GitList\Repository;

use GitList\Exception\InvalidRepositoryException;
use GitList\Exception\RepositoryNotFoundException;
use GitList\Repository;
use GitList\SCM\Repository as SourceRepository;
use GitList\SCM\System;
use Symfony\Component\Finder\Finder;

class Index
{
    /**
     * @var Repository[]
     */
    protected array $repositories = [];

    public function __construct(array $paths, array $excludePaths, string $depth, protected array $systems)
    {
        $finder = new Finder();

        foreach ($paths as $path) {
            $directories = $finder
                ->directories()
                ->depth($depth)
                ->ignoreUnreadableDirs()
                ->exclude($excludePaths)
                ->in($path);

            foreach ($directories as $directory) {
                $repository = new SourceRepository($directory->getRealPath());

                try {
                    $system = $this->getSystem($repository);
                } catch (InvalidRepositoryException) {
                    continue;
                }

                $this->addRepository(new Repository($system, $repository, $directory->getBasename()));
            }
        }
    }

    public function getSystem(SourceRepository $repository): System
    {
        foreach ($this->systems as $system) {
            if ($system->isValidRepository($repository)) {
                return $system;
            }
        }

        throw new InvalidRepositoryException($repository->getPath());
    }

    public function addRepository(Repository $repository): void
    {
        $this->repositories[$repository->getName()] = $repository;
    }

    public function getRepository(string $name): Repository
    {
        if (!isset($this->repositories[$name])) {
            throw new RepositoryNotFoundException($name);
        }

        return $this->repositories[$name];
    }

    public function getRepositories(): array
    {
        return $this->repositories;
    }
}
