<?php

declare(strict_types=1);

namespace GitList;

use GitList\SCM\Repository as SourceRepository;
use GitList\SCM\System\Git\CommandLine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RepositoryParentBranchTest extends TestCase
{
    protected string $path;

    public function setUp(): void
    {
        if (empty(shell_exec('which git 2> /dev/null'))) {
            $this->markTestSkipped('Git is not available.');
        }

        $this->path = sprintf('%s/gitlist-%s', sys_get_temp_dir(), uniqid());
        mkdir($this->path);

        $this->git(['-c', 'init.defaultBranch=master', 'init', '--quiet', '.']);
        $this->commit('m1');
        $this->git(['checkout', '--quiet', '-b', 'feature-a']);
        $this->commit('a1');
        $this->git(['checkout', '--quiet', '-b', 'feature-b']);
        $this->commit('b1');
        $this->git(['checkout', '--quiet', 'master']);
        $this->commit('m2');
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->path);
    }

    public function testIsGuessingDefaultBranchAsParent(): void
    {
        $repository = $this->createRepository();

        $this->assertSame('master', $repository->guessParentBranch('feature-a', ['master', 'feature-a', 'feature-b']));
    }

    public function testIsGuessingStackedBranchParent(): void
    {
        $repository = $this->createRepository();

        $this->assertSame('feature-a', $repository->guessParentBranch('feature-b', ['master', 'feature-a', 'feature-b']));
    }

    public function testIsNotGuessingWithoutOtherBranches(): void
    {
        $repository = $this->createRepository();

        $this->assertNull($repository->guessParentBranch('master', ['master']));
    }

    public function testIsNotGuessingForBranchEveryOtherBranchBuildsOn(): void
    {
        $this->git(['branch', 'base', 'master~1']);
        $repository = $this->createRepository();

        $this->assertNull($repository->guessParentBranch('base', ['master', 'feature-a', 'feature-b', 'base']));
    }

    protected function createRepository(): Repository
    {
        return new Repository(new CommandLine(), new SourceRepository($this->path), 'test');
    }

    protected function commit(string $file): void
    {
        file_put_contents(sprintf('%s/%s', $this->path, $file), "contents\n");

        $this->git(['add', $file]);
        $this->git(['-c', 'commit.gpgsign=false', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '--message', $file]);
    }

    /**
     * @param string[] $command
     */
    protected function git(array $command): void
    {
        (new Process([...['git'], ...$command], $this->path))->mustRun();
    }
}
