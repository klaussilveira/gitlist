<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use GitList\SCM\Blob;
use GitList\SCM\Tree;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RepositoryExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('getCommitish', [$this, 'getCommitish']),
            new TwigFunction('getParent', [$this, 'getParent']),
            new TwigFunction('getBreadcrumbs', [$this, 'getBreadcrumbs']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('formatFileSize', [$this, 'formatFileSize']),
            new TwigFilter('onlyTrees', [$this, 'onlyTrees']),
            new TwigFilter('onlyFiles', [$this, 'onlyFiles']),
        ];
    }

    public function onlyTrees($items): array
    {
        return array_filter($items, [$this, 'isTree']);
    }

    public function onlyFiles($items): array
    {
        return array_filter($items, fn ($item) => !$this->isTree($item));
    }

    public function isTree($value): bool
    {
        if (!$value) {
            return false;
        }

        return $value instanceof Tree;
    }

    public function getCommitish(string $hash, string $path): string
    {
        return $hash.'/'.$path;
    }

    public function getParent(string $path): string
    {
        $parent = dirname($path);

        if ('.' == $parent) {
            return '';
        }

        return $parent;
    }

    public function getBreadcrumbs(Blob $blob): array
    {
        $breadcrumbs = [];
        $parts = explode('/', $blob->getName());
        $previousPart = '';

        foreach ($parts as $index => $part) {
            $previousPart .= (0 == $index ? '' : '/').$part;
            $breadcrumbs[] = [
                'name' => $part,
                'commitish' => $this->getCommitish($blob->getHash(), $previousPart),
            ];
        }

        return $breadcrumbs;
    }

    public function formatFileSize($value = null): string
    {
        if (!$value) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($value) / log(1024));
        $pow = min($pow, count($units) - 1);
        $value /= 1024 ** $pow;

        return (string) round($value, 2).' '.$units[$pow];
    }
}
