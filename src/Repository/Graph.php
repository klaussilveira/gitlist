<?php

declare(strict_types=1);

namespace GitList\Repository;

use GitList\SCM\Commit;
use GitList\SCM\Item;

class Graph
{
    /**
     * @var list<array{commit: Commit, lane: int, width: int, lines: list<array{from: int, to: int, start: string, end: string}>}>
     */
    protected array $rows = [];

    /**
     * @param Commit[] $commits
     */
    public function __construct(array $commits)
    {
        $lanes = [];

        foreach ($commits as $commit) {
            $hash = $commit->getHash();
            $expecting = array_keys($lanes, $hash, true);
            $lane = $expecting[0] ?? $this->findFreeLane($lanes);
            $lines = [];

            foreach ($lanes as $index => $expected) {
                if (null === $expected) {
                    continue;
                }

                if ($expected === $hash) {
                    $lines[] = ['from' => $index, 'to' => $lane, 'start' => 'top', 'end' => 'middle'];
                    $lanes[$index] = null;

                    continue;
                }

                $lines[] = ['from' => $index, 'to' => $index, 'start' => 'top', 'end' => 'bottom'];
            }

            $width = count($lanes);
            $parents = array_map(fn (Item $parent): string => $parent->getHash(), $commit->getParents());

            foreach ($parents as $position => $parent) {
                $target = 0 === $position ? $lane : (array_keys($lanes, $parent, true)[0] ?? $this->findFreeLane($lanes));
                $lanes[$target] = $parent;
                $lines[] = ['from' => $lane, 'to' => $target, 'start' => 'middle', 'end' => 'bottom'];
            }

            $lanes += [$lane => null];

            while ($lanes && null === end($lanes)) {
                array_pop($lanes);
            }

            $this->rows[] = [
                'commit' => $commit,
                'lane' => $lane,
                'width' => max($width, count($lanes), $lane + 1),
                'lines' => $lines,
            ];
        }
    }

    /**
     * @return list<array{commit: Commit, lane: int, width: int, lines: list<array{from: int, to: int, start: string, end: string}>}>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @param array<int, ?string> $lanes
     */
    protected function findFreeLane(array $lanes): int
    {
        foreach ($lanes as $index => $expected) {
            if (null === $expected) {
                return $index;
            }
        }

        return count($lanes);
    }
}
