<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use GitList\SCM\Blob;
use GitList\SCM\Item;
use GitList\SCM\Tree;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class RepositoryExtension
{
    /**
     * @param Item[] $items
     *
     * @return Item[]
     */
    #[AsTwigFilter('onlyTrees')]
    public function onlyTrees(array $items): array
    {
        return array_filter($items, [$this, 'isTree']);
    }

    /**
     * @param Item[] $items
     *
     * @return Item[]
     */
    #[AsTwigFilter('onlyFiles')]
    public function onlyFiles(array $items): array
    {
        return array_filter($items, fn ($item) => !$this->isTree($item));
    }

    public function isTree(?Item $value): bool
    {
        if (!$value) {
            return false;
        }

        return $value instanceof Tree;
    }

    #[AsTwigFunction('getCommitish')]
    public function getCommitish(string $hash, string $path): string
    {
        return $hash.'/'.$path;
    }

    #[AsTwigFunction('getParent')]
    public function getParent(string $path): string
    {
        $parent = dirname($path);

        if ('.' == $parent) {
            return '';
        }

        return $parent;
    }

    /**
     * @return array<int, array{name: string, commitish: string}>
     */
    #[AsTwigFunction('getBreadcrumbs')]
    public function getBreadcrumbs(Blob $blob): array
    {
        $breadcrumbs = [];
        $parts = explode('/', $blob->getName() ?? '');
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

    #[AsTwigFilter('formatFileSize')]
    public function formatFileSize(?int $value = null): string
    {
        if (!$value) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = (int) min(floor(log($value) / log(1024)), count($units) - 1);
        $value /= 1024 ** $pow;

        return (string) round($value, 2).' '.$units[$pow];
    }
}
