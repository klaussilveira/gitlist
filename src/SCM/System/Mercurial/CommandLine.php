<?php

declare(strict_types=1);

namespace GitList\SCM\System\Mercurial;

use Carbon\CarbonImmutable;
use GitList\SCM\AnnotatedLine;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Branch;
use GitList\SCM\Commit;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Commit\Person;
use GitList\SCM\Diff\Parse;
use GitList\SCM\Exception\CommandException;
use GitList\SCM\Repository;
use GitList\SCM\Symlink;
use GitList\SCM\System;
use GitList\SCM\Tag;
use GitList\SCM\Tree;
use SimpleXMLElement;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CommandLine implements System
{
    public const DEFAULT_TIMEOUT = 3600;
    public const DEFAULT_COMMIT_FORMAT = '-T "<item><hash>{node}</hash><short_hash>{node|short}</short_hash><tree>{p1node}</tree><short_tree>{p1node|short}</short_tree><parent>{p1node}</parent><short_parent>{p1node|short}</short_parent><subject><![CDATA[{desc|firstline}]]></subject><author>{author|person}</author><author_email>{author|email}</author_email><author_date>{date|rfc822date}</author_date><commiter>{author|person}</commiter><commiter_email>{author|email}</commiter_email><commiter_date>{date|rfc822date}</commiter_date><body><![CDATA[{desc}]]></body></item>"';

    // Mercurial does not support ISO 8601 properly
    public const MERCURIAL_DATE_FORMAT = 'Y-m-d H:i:s';

    protected ?string $path;

    public function __construct(?string $path = null)
    {
        if (!$path) {
            $path = (new ExecutableFinder())->find('hg', '/usr/bin/hg');
        }

        $this->path = $path;
    }

    public function isValidRepository(Repository $repository): bool
    {
        $path = $repository->getPath();

        return file_exists($path) && file_exists($path.'/.hg');
    }

    public function getDescription(Repository $repository): string
    {
        $path = $repository->getPath();

        if (file_exists($path.'/.hg/hgrc')) {
            $hgrc = parse_ini_file($path.'/.hg/hgrc');

            return $hgrc['description'] ?? '';
        }

        return '';
    }

    public function getDefaultBranch(Repository $repository): string
    {
        return 'default';
    }

    public function getBranches(Repository $repository): array
    {
        $output = $this->run(['heads', '-T {bookmarks}||{node}\n'], $repository);
        $branchData = explode("\n", $output);
        $branches = [];

        foreach ($branchData as $branchItem) {
            if (empty($branchItem)) {
                continue;
            }

            $branchInfo = explode('||', $branchItem);
            $commit = $this->getCommit($repository, trim($branchInfo[1]));
            $branches[] = new Branch($repository, trim($branchInfo[0]), $commit);
        }

        return $branches;
    }

    public function getTags(Repository $repository): array
    {
        $output = $this->run(['tags', '-T', '{tag}||{node}||{node|short}||{author|person}||{author|email}||{date|rfc822date}||{desc|firstline}\n'], $repository);
        $tagData = explode("\n", $output);
        $tags = [];

        foreach ($tagData as $tagItem) {
            if (empty($tagItem)) {
                continue;
            }

            $tagInfo = explode('||', $tagItem);

            if (!isset($tagInfo[0])) {
                continue;
            }

            $author = new Person($tagInfo[3], $tagInfo[4]);
            $authoredAt = new CarbonImmutable($tagInfo[5]);
            $tag = new Tag($repository, $tagInfo[0], $author, $authoredAt);

            if (isset($tagInfo[1])) {
                $commit = new Commit($repository, $tagInfo[1], $tagInfo[2] ?? null);
                $tag->setTarget($commit);
            }

            if (isset($tagInfo[6])) {
                $tag->setSubject($tagInfo[6]);
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    public function getTree(Repository $repository, ?string $hash = 'tip'): Tree
    {
        $output = $this->run(['manifest', '-v', '--debug', '-r', $hash], $repository);

        return $this->buildTree($repository, $hash, $output);
    }

    public function getRecursiveTree(Repository $repository, ?string $hash = 'tip'): Tree
    {
        $output = $this->run(['manifest', '-v', '--debug', '-r', $hash], $repository);

        return $this->buildTree($repository, $hash, $output);
    }

    public function getPathTree(Repository $repository, string $path, ?string $hash = 'tip'): Tree
    {
        // Mercurial manifest doesn't seem to support path specification, so we filter here
        $tree = $this->getTree($repository, $hash);

        foreach ($tree->getChildren() as $child) {
            if (str_starts_with($child->getName(), $path)) {
                continue;
            }

            $tree->removeChild($child);
        }

        return $tree;
    }

    public function getCommit(Repository $repository, ?string $hash = 'tip'): Commit
    {
        $commitOutput = $this->run(['log', self::DEFAULT_COMMIT_FORMAT, '-r', $hash], $repository);
        $commits = $this->parseCommitDataXml($repository, $commitOutput);
        $commit = reset($commits);

        $diffOutput = $this->run(['diff', '--change', $hash], $repository);
        $commit->setRawDiffs($diffOutput);

        $fileDiffs = (new Parse())->fromRawBlock($diffOutput);
        $commit->setDiffs($fileDiffs);

        return $commit;
    }

    public function getCommits(Repository $repository, ?string $hash = 'tip', int $page = 1, int $perPage = 10): array
    {
        $range = sprintf('limit(branch("%s"), %d, %d)', $hash, $page * $perPage, ($page - 1) * $perPage);

        $output = $this->run([
            'log',
            self::DEFAULT_COMMIT_FORMAT,
            '-r',
            $range,
        ], $repository);

        return $this->parseCommitDataXml($repository, $output);
    }

    public function getCommitsFromPath(Repository $repository, string $path, ?string $hash = 'tip', int $page = 1, int $perPage = 10): array
    {
        $range = sprintf('limit(branch("%s"), %d, %d)', $hash, $page * $perPage, ($page - 1) * $perPage);

        $output = $this->run([
            'log',
            self::DEFAULT_COMMIT_FORMAT,
            '-r',
            $range,
            $path,
        ], $repository);

        return $this->parseCommitDataXml($repository, $output);
    }

    public function getSpecificCommits(Repository $repository, array $hashes): array
    {
        $output = $this->run(['log', self::DEFAULT_COMMIT_FORMAT, '-r', implode(':', $hashes)], $repository);

        return $this->parseCommitDataXml($repository, $output);
    }

    public function getBlame(Repository $repository, string $hash, string $path): Blame
    {
        $output = $this->run(['annotate', '-cv', '-r', $hash, $path], $repository);
        $blameLines = explode(PHP_EOL, $output);
        $annotatedLines = [];
        $commits = [];

        foreach ($blameLines as $blameLine) {
            if (empty($blameLine)) {
                continue;
            }

            $commit = substr($blameLine, 0, 12);
            $line = substr($blameLine, 14);

            $commits[] = $commit;
            $annotatedLines[] = [
                'commit' => $commit,
                'line' => $line,
            ];
        }

        $blame = new Blame($hash, $path);
        $commits = $this->getSpecificCommits($repository, array_unique($commits));

        foreach ($annotatedLines as $annotatedLine) {
            $commit = $commits[$annotatedLine['commit']];
            $blame->addAnnotatedLine(new AnnotatedLine($commit, $annotatedLine['line']));
        }

        return $blame;
    }

    public function getBlob(Repository $repository, string $hash, string $path): Blob
    {
        $output = $this->run(['cat', '-r', $hash, $path], $repository);
        $blob = new Blob($repository, $hash);
        $blob->setName(basename($path));
        $blob->setContents($output);

        return $blob;
    }

    public function searchCommits(Repository $repository, Criteria $criteria, ?string $hash = 'tip'): array
    {
        $command = ['log', self::DEFAULT_COMMIT_FORMAT];
        $commits = [];

        if ($criteria->getFrom() && $criteria->getTo()) {
            $command[] = '--date';
            $command[] = sprintf(
                '%s to %s',
                $criteria->getFrom()->format(self::MERCURIAL_DATE_FORMAT),
                $criteria->getTo()->format(self::MERCURIAL_DATE_FORMAT)
            );
        }

        if ($criteria->getFrom() && !$criteria->getTo()) {
            $command[] = '--date';
            $command[] = '>'.$criteria->getFrom()->format(self::MERCURIAL_DATE_FORMAT);
        }

        if (!$criteria->getFrom() && $criteria->getTo()) {
            $command[] = '--date';
            $command[] = '<'.$criteria->getTo()->format(self::MERCURIAL_DATE_FORMAT);
        }

        if ($criteria->getAuthor()) {
            $command[] = '--user';
            $command[] = $criteria->getAuthor();
        }

        if ($criteria->getMessage()) {
            $command[] = '--keyword';
            $command[] = $criteria->getMessage();
        }

        $command[] = '-r';
        $command[] = sprintf('sort(branch("%s"), -date)', $hash);
        $output = $this->run($command, $repository);
        $commits += $this->parseCommitDataXml($repository, $output);

        return $commits;
    }

    public function archive(Repository $repository, string $format, string $hash, string $path = ''): string
    {
        $destination = sprintf('%s/%s.%s', sys_get_temp_dir(), $hash, $format);
        $this->run(['archive', '-r', $hash, '-I', $path, $destination], $repository);

        return $destination;
    }

    protected function run(array $command, ?Repository $repository = null): string
    {
        array_unshift($command, $this->path);

        $process = new Process($command);
        $process->setTimeout(self::DEFAULT_TIMEOUT);

        if ($repository) {
            $process->setWorkingDirectory($repository->getPath());
        }

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new CommandException($exception->getProcess()->getErrorOutput());
        }

        return $process->getOutput();
    }

    protected function parseCommitDataXml(Repository $repository, string $input): array
    {
        $items = new SimpleXMLElement('<items>'.$input.'</items>');
        $commits = [];

        foreach ($items as $item) {
            $commit = new Commit($repository, (string) $item->hash, (string) $item->short_hash);
            $commit->setTree(new Tree($repository, (string) $item->tree, (string) $item->short_tree));

            $parents = explode(' ', (string) $item->parent);
            $shortParents = explode(' ', (string) $item->short_parent);
            foreach ($parents as $key => $parent) {
                $commit->addParent(new Commit($repository, $parent, $shortParents[$key] ?? null));
            }

            $commit->setSubject((string) $item->subject);
            $commit->setBody((string) $item->body);
            $commit->setAuthor(new Person((string) $item->author, (string) $item->author_email));
            $commit->setAuthoredAt(new CarbonImmutable((string) $item->author_date));
            $commit->setCommiter(new Person((string) $item->commiter, (string) $item->commiter_email));
            $commit->setCommitedAt(new CarbonImmutable((string) $item->commiter_date));

            $commits[(string) $item->short_hash] = $commit;
        }

        return $commits;
    }

    protected function buildTree(Repository $repository, string $hash, string $output): Tree
    {
        $lines = explode("\n", $output);
        $root = new Tree($repository, $hash);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $file = preg_split('/[\s]+/', $line, 4);

            if ('.hgtags' == $file[2]) {
                continue;
            }

            if ('@' == $file[2]) {
                $symlinkTarget = $this->run(['cat', '-r', $hash, $file[3]], $repository);
                $symlink = new Symlink($repository, $file[0]);
                $symlink->setMode($file[1]);
                $symlink->setName($file[3]);
                $symlink->setSize(0);
                $symlink->setTarget($symlinkTarget);
                $root->addChild($symlink);

                continue;
            }

            $blob = new Blob($repository, $file[0]);
            $blob->setMode($file[1]);
            $blob->setName($file[2]);
            $blob->setSize(0);
            $root->addChild($blob);
        }

        return $root;
    }
}
