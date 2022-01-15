<?php

declare(strict_types=1);

namespace GitList\SCM\System\Git;

use Carbon\CarbonImmutable;
use DateTime;
use GitList\SCM\AnnotatedLine;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Branch;
use GitList\SCM\Commit;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Commit\Person;
use GitList\SCM\Commit\Signature;
use GitList\SCM\Diff\Parse;
use GitList\SCM\Exception\CommandException;
use GitList\SCM\Exception\InvalidCommitException;
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
    public const DEFAULT_COMMIT_FORMAT = '--pretty=format:<item><hash>%H</hash><short_hash>%h</short_hash><tree>%T</tree><short_tree>%t</short_tree><parent>%P</parent><short_parent>%p</short_parent><subject><![CDATA[%s]]></subject><author>%aN</author><author_email>%aE</author_email><author_date>%aD</author_date><commiter>%cN</commiter><commiter_email>%cE</commiter_email><commiter_date>%cD</commiter_date><signer><![CDATA[%GS]]></signer><signer_key>%GK</signer_key><valid_signature>%G?</valid_signature><body><![CDATA[%b]]></body></item>';

    protected ?string $path;

    public function __construct(string $path = null)
    {
        if (!$path) {
            $path = (new ExecutableFinder())->find('git', '/usr/bin/git');
        }

        $this->path = $path;
    }

    public function isValidRepository(Repository $repository): bool
    {
        $path = $repository->getPath();

        return file_exists($path) && (file_exists($path . '/.git/HEAD') || file_exists($path . '/HEAD'));
    }

    public function getDescription(Repository $repository): string
    {
        $path = $repository->getPath();

        if (file_exists($path . '/description')) {
            return file_get_contents($path . '/description');
        }

        if (file_exists($path . '/.git/description')) {
            return file_get_contents($path . '/.git/description');
        }

        return '';
    }

    public function getDefaultBranch(Repository $repository): string
    {
        $branch = $this->run(['symbolic-ref', '--short', 'HEAD'], $repository);

        return trim($branch);
    }

    public function getBranches(Repository $repository): array
    {
        $output = $this->run(['for-each-ref', 'refs/heads', '--format=%(refname:short)||%(objectname)||%(objectname:short)||%(authorname)||%(authoremail)||%(authordate)||%(subject)'], $repository);
        $branchData = explode(PHP_EOL, $output);
        $branches = [];

        foreach ($branchData as $branchItem) {
            if (empty($branchItem)) {
                continue;
            }

            $branchInfo = explode('||', $branchItem);

            $commit = new Commit($repository, $branchInfo[1], $branchInfo[2] ?? null);
            $commit->setAuthor(new Person($branchInfo[3], trim($branchInfo[4], '<>')));
            $commit->setAuthoredAt(new CarbonImmutable($branchInfo[5]));

            if (isset($branchInfo[6])) {
                $commit->setSubject($branchInfo[6]);
            }

            $branches[] = new Branch($repository, $branchInfo[0], $commit);
        }

        return $branches;
    }

    public function getTags(Repository $repository): array
    {
        $output = $this->run(['for-each-ref', 'refs/tags', '--format=%(refname:short)||%(objectname)||%(objectname:short)||%(taggername)||%(taggeremail)||%(taggerdate)||%(subject)'], $repository);
        $tagData = explode(PHP_EOL, $output);
        $tags = [];

        foreach ($tagData as $tagItem) {
            if (empty($tagItem)) {
                continue;
            }

            $tagInfo = explode('||', $tagItem);

            if (!isset($tagInfo[0])) {
                continue;
            }

            $author = new Person($tagInfo[3], trim($tagInfo[4], '<>'));
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

    public function getTree(Repository $repository, ?string $hash = 'HEAD'): Tree
    {
        $output = $this->run(['ls-tree', '-lz', '--full-tree', '--', $hash], $repository);

        return $this->buildTreeFromOutput($repository, $hash, $output, true);
    }

    public function getRecursiveTree(Repository $repository, ?string $hash = 'HEAD'): Tree
    {
        $output = $this->run(['ls-tree', '-lzr', '--full-tree', '--', $hash], $repository);

        return $this->buildTreeFromOutput($repository, $hash, $output);
    }

    public function getPathTree(Repository $repository, string $path, ?string $hash = 'HEAD'): Tree
    {
        $path = rtrim($path, '/') . '/';
        $output = $this->run(['ls-tree', '-lz', $hash, '--', $path], $repository);
        $tree = $this->buildTreeFromOutput($repository, $hash, $output, true);
        $tree->setName(rtrim($path, '/'));

        return $tree;
    }

    public function getCommit(Repository $repository, ?string $hash = 'HEAD'): Commit
    {
        $output = $this->run(['show', '--ignore-blank-lines', '-w', '-b', '--cc', self::DEFAULT_COMMIT_FORMAT, $hash], $repository);
        $commits = $this->parseCommitDataXml($repository, $output);
        $commit = reset($commits);

        $rawDiffBlock = substr($output, strpos($output, '</item>') + 7);
        $commit->setRawDiffs($rawDiffBlock);

        $fileDiffs = (new Parse())->fromRawBlock($rawDiffBlock);
        $commit->setDiffs($fileDiffs);

        return $commit;
    }

    public function getCommits(Repository $repository, ?string $hash = 'HEAD', int $page = 1, int $perPage = 10): array
    {
        $output = $this->run([
            'log',
            '--skip',
            ($page - 1) * $perPage,
            '--max-count',
            $page * $perPage,
            self::DEFAULT_COMMIT_FORMAT,
            $hash,
        ], $repository);

        return $this->parseCommitsDataXml($repository, $output);
    }

    public function getCommitsFromPath(Repository $repository, string $path, ?string $hash = 'HEAD', int $page = 1, int $perPage = 10): array
    {
        $output = $this->run([
            'log',
            '--skip',
            ($page - 1) * $perPage,
            '--max-count',
            $page * $perPage,
            self::DEFAULT_COMMIT_FORMAT,
            $hash,
            '--',
            $path,
        ], $repository);

        return $this->parseCommitsDataXml($repository, $output);
    }

    public function getSpecificCommits(Repository $repository, array $hashes): array
    {
        $output = $this->run([...['show', '-s', self::DEFAULT_COMMIT_FORMAT], ...$hashes], $repository);

        return $this->parseCommitsDataXml($repository, $output);
    }

    public function getBlame(Repository $repository, string $hash, string $path): Blame
    {
        $output = $this->run(['blame', '--root', '-ls', $hash, '--', $path], $repository);
        $blameLines = explode(PHP_EOL, $output);
        $annotatedLines = [];
        $commits = [];

        foreach ($blameLines as $blameLine) {
            if (empty($blameLine)) {
                continue;
            }

            $blameParts = [];
            preg_match('/([a-zA-Z0-9^]{40})\s+.*?([0-9]+)\)\s+(.+)?/', $blameLine, $blameParts);

            $commits[] = $blameParts[1];
            $annotatedLines[] = [
                'commit' => $blameParts[1],
                'line' => ltrim(str_replace($blameParts[1], '', $blameParts[0])),
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
        $commits = $this->getCommitsFromPath($repository, $path, $hash, 1, 1);
        $commit = reset($commits);
        $blobOutput = $this->run(['show', sprintf('%s:%s', $hash, $path)], $repository);

        $blob = new Blob($repository, $commit->getHash(), $commit->getShortHash());
        $blob->setName($path);
        $blob->setContents($blobOutput);

        return $blob;
    }

    public function searchCommits(Repository $repository, Criteria $criteria, ?string $hash = 'HEAD'): array
    {
        $command = ['log', self::DEFAULT_COMMIT_FORMAT];

        if ($criteria->getFrom()) {
            $command[] = '--after';
            $command[] = $criteria->getFrom()->format(DateTime::ISO8601);
        }

        if ($criteria->getTo()) {
            $command[] = '--before';
            $command[] = $criteria->getTo()->format(DateTime::ISO8601);
        }

        if ($criteria->getAuthor()) {
            $command[] = '--author';
            $command[] = $criteria->getAuthor();
        }

        if ($criteria->getMessage()) {
            $command[] = '--grep';
            $command[] = $criteria->getMessage();
        }

        $command[] = $hash;
        $output = $this->run($command, $repository);

        return $this->parseCommitsDataXml($repository, $output);
    }

    public function archive(Repository $repository, string $format, string $hash, string $path = '.'): string
    {
        $destination = sprintf('%s/%s.%s', sys_get_temp_dir(), $hash, $format);

        $this->run(['archive', '--output', $destination, $hash, '--', $path], $repository);

        return $destination;
    }

    protected function run(array $command, Repository $repository = null): string
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

    protected function buildTreeFromOutput(Repository $repository, string $hash, string $output, bool $fetchCommitInfo = false): Tree
    {
        $lines = explode("\0", $output);
        $root = new Tree($repository, $hash);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $file = preg_split('/[\s]+/', $line, 5);

            if ($file[1] == 'commit') {
                // Don't handle submodules yet
                continue;
            }

            if ($file[0] == '120000') {
                $symlinkTarget = $this->run(['show', $file[2]], $repository);
                $symlink = new Symlink($repository, $file[2]);
                $symlink->setMode($file[0]);
                $symlink->setName($file[4]);
                $symlink->setSize((int) $file[3]);
                $symlink->setTarget($symlinkTarget);
                $root->addChild($symlink);

                continue;
            }

            if ($file[1] == 'blob') {
                $blob = new Blob($repository, $file[2]);
                $blob->setMode($file[0]);
                $blob->setName($file[4]);
                $blob->setSize((int) $file[3]);

                if ($fetchCommitInfo) {
                    try {
                        $blob->addParent($this->getLatestCommitFromPath($repository, $file[4], $hash));
                    } catch (InvalidCommitException) {
                        // Do not add parent
                    }
                }

                $root->addChild($blob);

                continue;
            }

            $tree = new Tree($repository, $file[2]);
            $tree->setMode($file[0]);
            $tree->setName($file[4]);

            if ($fetchCommitInfo) {
                try {
                    $tree->addParent($this->getLatestCommitFromPath($repository, $file[4], $hash));
                } catch (InvalidCommitException) {
                    // Do not add parent
                }
            }

            $root->addChild($tree);
        }

        return $root;
    }

    protected function getLatestCommitFromPath(Repository $repository, string $path, string $hash): Commit
    {
        $output = $this->run(['log', '-n', 1, self::DEFAULT_COMMIT_FORMAT, $hash, '--', $path], $repository);
        $commits = $this->parseCommitDataXml($repository, $output);

        return reset($commits);
    }

    protected function parseCommitDataXml(Repository $repository, string $input): array
    {
        $xmlStart = strpos($input, '<item>');

        if ($xmlStart === false) {
            throw new InvalidCommitException($input);
        }

        $xmlEnd = strpos($input, '</item>') + 7;
        $xml = substr($input, $xmlStart, $xmlEnd);

        return $this->parseCommitsDataXml($repository, $xml);
    }

    protected function parseCommitsDataXml(Repository $repository, string $input): array
    {
        $items = new SimpleXMLElement('<items>' . $input . '</items>');
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

            $signatureStatus = (string) $item->valid_signature;
            if ($signatureStatus != 'N') {
                $signature = new Signature((string) $item->signer, (string) $item->signer_key);

                if ($signatureStatus == 'B') {
                    $signature->validate();
                }

                $commit->setSignature($signature);
            }

            $commits[$commit->getHash()] = $commit;
        }

        return $commits;
    }
}
