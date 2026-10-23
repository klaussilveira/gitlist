<?php

declare(strict_types=1);

namespace GitList\Repository;

use GitList\SCM\Commit;
use GitList\SCM\Repository;
use PHPUnit\Framework\TestCase;

class GraphTest extends TestCase
{
    public function testIsKeepingLinearHistoryOnOneLane(): void
    {
        $graph = new Graph([$this->commit('c1', 'c2'), $this->commit('c2', 'c3'), $this->commit('c3')]);

        $this->assertEquals([
            ['c1', 0, 1, ['0>0 middle-bottom']],
            ['c2', 0, 1, ['0>0 top-middle', '0>0 middle-bottom']],
            ['c3', 0, 1, ['0>0 top-middle']],
        ], $this->describe($graph));
    }

    public function testIsJoiningBranchesAtTheirForkPoint(): void
    {
        $graph = new Graph([$this->commit('m', 'b'), $this->commit('f', 'b'), $this->commit('b', 'r'), $this->commit('r')]);

        $this->assertEquals([
            ['m', 0, 1, ['0>0 middle-bottom']],
            ['f', 1, 2, ['0>0 top-bottom', '1>1 middle-bottom']],
            ['b', 0, 2, ['0>0 top-middle', '1>0 top-middle', '0>0 middle-bottom']],
            ['r', 0, 1, ['0>0 top-middle']],
        ], $this->describe($graph));
    }

    public function testIsSplittingMergeIntoParentLanes(): void
    {
        $graph = new Graph([$this->commit('m', 'a', 'f'), $this->commit('f', 'a'), $this->commit('a')]);

        $this->assertEquals([
            ['m', 0, 2, ['0>0 middle-bottom', '0>1 middle-bottom']],
            ['f', 1, 2, ['0>0 top-bottom', '1>1 top-middle', '1>1 middle-bottom']],
            ['a', 0, 2, ['0>0 top-middle', '1>0 top-middle']],
        ], $this->describe($graph));
    }

    public function testIsReusingFreedLanes(): void
    {
        $graph = new Graph([
            $this->commit('a', 'z'),
            $this->commit('b', 'z'),
            $this->commit('c', 'w'),
            $this->commit('z', 'w'),
            $this->commit('d', 'w'),
            $this->commit('w'),
        ]);

        $this->assertEquals([
            ['a', 0, 1, ['0>0 middle-bottom']],
            ['b', 1, 2, ['0>0 top-bottom', '1>1 middle-bottom']],
            ['c', 2, 3, ['0>0 top-bottom', '1>1 top-bottom', '2>2 middle-bottom']],
            ['z', 0, 3, ['0>0 top-middle', '1>0 top-middle', '2>2 top-bottom', '0>0 middle-bottom']],
            ['d', 1, 3, ['0>0 top-bottom', '2>2 top-bottom', '1>1 middle-bottom']],
            ['w', 0, 3, ['0>0 top-middle', '1>0 top-middle', '2>0 top-middle']],
        ], $this->describe($graph));
    }

    public function testIsContinuingLanesToParentsOutsideTheGraph(): void
    {
        $graph = new Graph([$this->commit('m', 'a', 'f')]);

        $this->assertEquals([
            ['m', 0, 2, ['0>0 middle-bottom', '0>1 middle-bottom']],
        ], $this->describe($graph));
    }

    protected function commit(string $hash, string ...$parents): Commit
    {
        $repository = new Repository('/tmp');
        $commit = new Commit($repository, $hash);

        foreach ($parents as $parent) {
            $commit->addParent(new Commit($repository, $parent));
        }

        return $commit;
    }

    /**
     * @return list<array{string, int, int, list<string>}>
     */
    protected function describe(Graph $graph): array
    {
        return array_map(fn (array $row): array => [
            $row['commit']->getHash(),
            $row['lane'],
            $row['width'],
            array_map(fn (array $line): string => sprintf('%d>%d %s-%s', $line['from'], $line['to'], $line['start'], $line['end']), $row['lines']),
        ], $graph->getRows());
    }
}
