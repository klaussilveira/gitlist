<?php

declare(strict_types=1);

namespace GitList\SCM\System\Git;

use Carbon\CarbonImmutable;
use DateTime;
use Exception;
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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CommandLine implements System
{
    public const DEFAULT_TIMEOUT = 3600;

    public const COMMIT_FIELDS = [
        '%H', '%h', '%T', '%t', '%P', '%p',
        '%aN', '%aE', '%aD', '%cN', '%cE', '%cD',
        '%GS', '%GK', '%G?', '%s', '%b',
    ];

    public const DEFAULT_BRANCH_CANDIDATES = ['master', 'main', 'trunk'];

    protected string $path;

    public function __construct(?string $path = null)
    {
        if (!$path) {
            $path = (new ExecutableFinder())->find('git') ?? '/usr/bin/git';
        }

        $this->path = $path;
    }

    public function isValidRepository(Repository $repository): bool
    {
        $path = $repository->getPath();

        return file_exists($path) && (file_exists($path.'/.git/HEAD') || file_exists($path.'/HEAD'));
    }

    public function getDescription(Repository $repository): string
    {
        $path = $repository->getPath();

        if (file_exists($path.'/description')) {
            return file_get_contents($path.'/description') ?: '';
        }

        if (file_exists($path.'/.git/description')) {
            return file_get_contents($path.'/.git/description') ?: '';
        }

        return '';
    }

    public function getDefaultBranch(Repository $repository): string
    {
        $head = $this->getResolvableHead($repository);

        if ($head) {
            return $head;
        }

        $branches = array_map(static fn (Branch $branch): string => $branch->getName(), $this->getBranches($repository));

        foreach (self::DEFAULT_BRANCH_CANDIDATES as $candidate) {
            if (in_array($candidate, $branches, true)) {
                return $candidate;
            }
        }

        return $branches[0] ?? trim($this->run(['symbolic-ref', '--short', 'HEAD'], $repository));
    }

    /**
     * @return Branch[]
     */
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
            $commit->setAuthoredAt($this->parseDate($branchInfo[5]));

            if (isset($branchInfo[6])) {
                $commit->setSubject($branchInfo[6]);
            }

            $branches[] = new Branch($repository, $branchInfo[0], $commit);
        }

        return $branches;
    }

    /**
     * @return Tag[]
     */
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

            $author = new Person($tagInfo[3], trim($tagInfo[4], '<>'));
            $authoredAt = $this->parseDate($tagInfo[5]);
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
        $hash = $this->resolveHash($repository, $hash);

        $output = $this->run(['ls-tree', '-lz', '--full-tree', '--', $hash], $repository);

        return $this->buildTreeFromOutput($repository, $hash, $output, true);
    }

    public function getRecursiveTree(Repository $repository, ?string $hash = 'HEAD'): Tree
    {
        $hash = $this->resolveHash($repository, $hash);

        $output = $this->run(['ls-tree', '-lzr', '--full-tree', '--', $hash], $repository);

        return $this->buildTreeFromOutput($repository, $hash, $output);
    }

    public function getPathTree(Repository $repository, string $path, ?string $hash = 'HEAD'): Tree
    {
        $hash = $this->resolveHash($repository, $hash);

        $path = rtrim($path, '/').'/';
        $output = $this->run(['ls-tree', '-lz', $hash, '--', $path], $repository);
        $tree = $this->buildTreeFromOutput($repository, $hash, $output, true);
        $tree->setName(rtrim($path, '/'));

        return $tree;
    }

    public function getCommit(Repository $repository, ?string $hash = 'HEAD'): Commit
    {
        $hash = $this->resolveHash($repository, $hash);

        $delimiter = $this->generateSafeCommitDelimiter();
        $output = $this->run(['show', '--no-textconv', '--ignore-blank-lines', '-w', '-b', '--cc', $this->getCommitFormat($delimiter), $hash], $repository);
        [$commit, $rawDiffBlock] = $this->parseFirstCommitData($repository, $output, $delimiter);

        $commit->setRawDiffs($rawDiffBlock);

        $fileDiffs = (new Parse())->fromRawBlock($rawDiffBlock);
        $commit->setDiffs($fileDiffs);

        return $commit;
    }

    /**
     * @return array<string, Commit>
     */
    public function getCommits(Repository $repository, ?string $hash = 'HEAD', int $page = 1, int $perPage = 10): array
    {
        $hash = $this->resolveHash($repository, $hash);

        $delimiter = $this->generateSafeCommitDelimiter();
        $output = $this->run([
            'log',
            '--skip',
            (string) (($page - 1) * $perPage),
            '--max-count',
            (string) $perPage,
            $this->getCommitFormat($delimiter),
            $hash,
        ], $repository);

        return $this->parseCommitsData($repository, $output, $delimiter);
    }

    /**
     * @return array<string, Commit>
     */
    public function getCommitsFromPath(Repository $repository, string $path, ?string $hash = 'HEAD', int $page = 1, int $perPage = 10): array
    {
        $hash = $this->resolveHash($repository, $hash);

        $delimiter = $this->generateSafeCommitDelimiter();
        $output = $this->run([
            'log',
            '--skip',
            (string) (($page - 1) * $perPage),
            '--max-count',
            (string) $perPage,
            $this->getCommitFormat($delimiter),
            $hash,
            '--',
            $path,
        ], $repository);

        return $this->parseCommitsData($repository, $output, $delimiter);
    }

    /**
     * @param string[] $hashes
     *
     * @return array<string, Commit>
     */
    public function getSpecificCommits(Repository $repository, array $hashes): array
    {
        $delimiter = $this->generateSafeCommitDelimiter();
        $output = $this->run([...['show', '-s', $this->getCommitFormat($delimiter)], ...$hashes], $repository);

        return $this->parseCommitsData($repository, $output, $delimiter);
    }

    public function getBlame(Repository $repository, string $hash, string $path): Blame
    {
        $hash = $this->resolveHash($repository, $hash);

        $output = $this->run(['blame', '--no-textconv', '--root', '-ls', $hash, '--', $path], $repository);
        $blameLines = explode(PHP_EOL, $output);
        $annotatedLines = [];
        $commits = [];

        foreach ($blameLines as $blameLine) {
            if (empty($blameLine)) {
                continue;
            }

            $blameParts = [];

            if (!preg_match('/([a-zA-Z0-9^]{40})\s+.*?([0-9]+)\)\s+(.+)?/', $blameLine, $blameParts)) {
                continue;
            }

            $commits[] = $blameParts[1];
            $annotatedLines[] = [
                'commit' => $blameParts[1],
                'line' => ltrim(str_replace($blameParts[1], '', $blameParts[0])),
            ];
        }

        $blame = new Blame($path, $hash);
        $commits = $this->getSpecificCommits($repository, array_unique($commits));

        foreach ($annotatedLines as $annotatedLine) {
            $commit = $commits[$annotatedLine['commit']];
            $blame->addAnnotatedLine(new AnnotatedLine($commit, $annotatedLine['line']));
        }

        return $blame;
    }

    public function getBlob(Repository $repository, string $hash, string $path): Blob
    {
        $hash = $this->resolveHash($repository, $hash);

        $commits = $this->getCommitsFromPath($repository, $path, $hash, 1, 1);
        $commit = reset($commits);
        $blobOutput = $this->run(['show', sprintf('%s:%s', $hash, $path)], $repository);

        if (!$commit) {
            throw new InvalidCommitException($path);
        }

        $blob = new Blob($repository, $commit->getHash(), $commit->getShortHash());
        $blob->setName($path);
        $blob->setContents($blobOutput);

        return $blob;
    }

    /**
     * @return array<string, Commit>
     */
    public function searchCommits(Repository $repository, Criteria $criteria, ?string $hash = 'HEAD'): array
    {
        $hash = $this->resolveHash($repository, $hash);

        $delimiter = $this->generateSafeCommitDelimiter();
        $command = ['log', $this->getCommitFormat($delimiter)];

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

        return $this->parseCommitsData($repository, $output, $delimiter);
    }

    public function archive(Repository $repository, string $format, string $hash, string $path = '.'): string
    {
        $hash = $this->resolveHash($repository, $hash);

        $destination = sprintf('%s/%s.%s', sys_get_temp_dir(), $hash, $format);

        $this->run(['archive', '--output', $destination, $hash, '--', $path], $repository);

        return $destination;
    }

    /**
     * @param string[] $command
     */
    protected function run(array $command, ?Repository $repository = null): string
    {
        if ($repository) {
            array_unshift($command, '-c', 'safe.directory='.realpath($repository->getPath()));
        }

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

    protected function resolveHash(Repository $repository, ?string $hash): string
    {
        if ($hash && 'HEAD' !== $hash) {
            return $hash;
        }

        if ($this->isValidHash($repository, 'HEAD')) {
            return 'HEAD';
        }

        return $this->getDefaultBranch($repository);
    }

    protected function getResolvableHead(Repository $repository): ?string
    {
        try {
            return trim($this->run(['rev-parse', '--verify', '--quiet', '--abbrev-ref', 'HEAD'], $repository));
        } catch (CommandException) {
            return null;
        }
    }

    protected function isValidHash(Repository $repository, string $hash): bool
    {
        try {
            $this->run(['rev-parse', '--verify', '--quiet', $hash], $repository);

            return true;
        } catch (CommandException) {
            return false;
        }
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

            if (false === $file) {
                continue;
            }

            if ('commit' == $file[1]) {
                // Don't handle submodules yet
                continue;
            }

            if ('120000' == $file[0]) {
                $symlinkTarget = $this->run(['show', $file[2]], $repository);
                $symlink = new Symlink($repository, $file[2]);
                $symlink->setMode($file[0]);
                $symlink->setName($file[4]);
                $symlink->setSize((int) $file[3]);
                $symlink->setTarget($symlinkTarget);
                $root->addChild($symlink);

                continue;
            }

            if ('blob' == $file[1]) {
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
        $delimiter = $this->generateSafeCommitDelimiter();
        $output = $this->run(['log', '-n', '1', $this->getCommitFormat($delimiter), $hash, '--', $path], $repository);
        [$commit] = $this->parseFirstCommitData($repository, $output, $delimiter);

        return $commit;
    }

    protected function getCommitFormat(string $delimiter): string
    {
        return '--pretty=format:'.implode($delimiter, self::COMMIT_FIELDS).$delimiter;
    }

    /**
     * @return non-empty-string
     */
    protected function generateSafeCommitDelimiter(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param non-empty-string $delimiter
     *
     * @return array<string, Commit>
     */
    protected function parseCommitsData(Repository $repository, string $input, string $delimiter): array
    {
        $fieldCount = count(self::COMMIT_FIELDS);
        $records = array_chunk(explode($delimiter, $input), $fieldCount);
        $commits = [];

        foreach ($records as $fields) {
            if (count($fields) < $fieldCount) {
                continue;
            }

            $commit = $this->buildCommit($repository, $fields);
            $commits[$commit->getHash()] = $commit;
        }

        return $commits;
    }

    /**
     * @param non-empty-string $delimiter
     *
     * @return array{Commit, string}
     */
    protected function parseFirstCommitData(Repository $repository, string $input, string $delimiter): array
    {
        $fieldCount = count(self::COMMIT_FIELDS);
        $fields = explode($delimiter, $input);

        if (count($fields) <= $fieldCount) {
            throw new InvalidCommitException($input);
        }

        return [
            $this->buildCommit($repository, array_slice($fields, 0, $fieldCount)),
            $fields[$fieldCount],
        ];
    }

    /**
     * @param string[] $fields
     */
    protected function buildCommit(Repository $repository, array $fields): Commit
    {
        [
            $hash,
            $shortHash,
            $tree,
            $shortTree,
            $parents,
            $shortParents,
            $author,
            $authorEmail,
            $authorDate,
            $commiter,
            $commiterEmail,
            $commiterDate,
            $signer,
            $signerKey,
            $signatureStatus,
            $subject,
            $body,
        ] = $fields;

        $commit = new Commit($repository, ltrim($hash, "\r\n"), $shortHash);
        $commit->setTree(new Tree($repository, $tree, $shortTree));

        $shortParents = explode(' ', $shortParents);
        foreach (explode(' ', $parents) as $key => $parent) {
            $commit->addParent(new Commit($repository, $parent, $shortParents[$key] ?? null));
        }

        $commit->setSubject($subject);
        $commit->setBody($body);
        $commit->setAuthor(new Person($author, $authorEmail));
        $commit->setAuthoredAt($this->parseDate($authorDate));
        $commit->setCommiter(new Person($commiter, $commiterEmail));
        $commit->setCommitedAt($this->parseDate($commiterDate));

        if ('N' != $signatureStatus) {
            $signature = new Signature($signer, $signerKey);

            if ('B' == $signatureStatus) {
                $signature->validate();
            }

            $commit->setSignature($signature);
        }

        return $commit;
    }

    protected function parseDate(string $date): CarbonImmutable
    {
        try {
            return new CarbonImmutable($date);
        } catch (Exception) {
            return CarbonImmutable::createFromTimestamp(0);
        }
    }
}
