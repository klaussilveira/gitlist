<?php

declare(strict_types=1);

namespace GitList\SCM\Diff;

class Parse
{
    private const TOKENS = [
        '/^diff --c\s/' => 'start',
        '/^diff --cc\s/' => 'start',
        '/^diff --git\s/' => 'start',
        '/^diff -r\s/' => 'start',
        '/^new file mode \d+$/' => 'newFile',
        '/^deleted file mode \d+$/' => 'deletedFile',
        '/^index\s[\da-zA-Z]+\.\.[\da-zA-Z]+(\s(\d+))?$/' => 'index',
        '/^index\s([\da-zA-Z]+,)([\da-zA-Z]+\.\.[\da-zA-Z]+)(\s(\d+))?$/' => 'mergeIndex',
        '/^---\s/' => 'fromFile',
        '/^\+\+\+\s/' => 'toFile',
        '/^@@\s+(\-(\d+),?(\d+)?\s+)+(\+(\d+),?(\d+)?\s)+@@/' => 'hunk',
        '/^@@@\s+(\-(\d+),?(\d+)?\s+)+(\+(\d+),?(\d+)?\s)+@@@/' => 'hunk',
        '/^-/' => 'deletedLine',
        '/^\+/' => 'addedLine',
    ];

    /**
     * @var File[]
     */
    protected array $files = [];
    protected ?File $currentFile = null;
    protected ?Hunk $currentHunk = null;
    protected int $oldCounter = 0;
    protected int $newCounter = 0;
    protected int $deletedLines = 0;
    protected int $addedLines = 0;

    /**
     * @return File[]
     */
    public function fromRawBlock(string $rawBlock): array
    {
        $rawLines = explode(PHP_EOL, $rawBlock);
        $this->files = [];

        foreach ($rawLines as $rawLine) {
            $matched = false;

            foreach (self::TOKENS as $pattern => $action) {
                $matches = [];

                if (preg_match($pattern, $rawLine, $matches)) {
                    $this->$action($rawLine, $matches);
                    $matched = true;

                    break;
                }
            }

            if (!$matched) {
                $this->line($rawLine);
            }
        }

        $this->clearAccumulator();

        return $this->files;
    }

    /**
     * @param string[] $context
     */
    protected function start(string $line, array $context): void
    {
        $this->clearAccumulator();

        $line = str_replace($context[0], '', $line);
        $files = explode(' ', $line);
        $filename = str_replace('a/', '', $files[0]);

        $this->currentFile = new File($filename);
    }

    /**
     * @param string[] $context
     */
    protected function newFile(string $line, array $context): void
    {
        if (!$this->currentFile) {
            return;
        }

        $this->currentFile->setType(File::TYPE_NEW);
    }

    /**
     * @param string[] $context
     */
    protected function deletedFile(string $line, array $context): void
    {
        if (!$this->currentFile) {
            return;
        }

        $this->currentFile->setType(File::TYPE_DELETED);
    }

    /**
     * @param string[] $context
     */
    protected function index(string $line, array $context): void
    {
        if (!$this->currentFile) {
            return;
        }

        $headerParts = explode(' ', $line);

        $this->currentFile->setIndex($headerParts[1]);
    }

    /**
     * @param string[] $context
     */
    protected function mergeIndex(string $line, array $context): void
    {
        if (!$this->currentFile) {
            return;
        }

        $this->currentFile->setIndex($context[2]);
    }

    /**
     * @param string[] $context
     */
    protected function fromFile(string $line, array $context): void
    {
        if (!$this->currentFile) {
            return;
        }

        $this->currentFile->setFrom(trim($line, '- '));
    }

    /**
     * @param string[] $context
     */
    protected function toFile(string $line, array $context): void
    {
        if (!$this->currentFile) {
            return;
        }

        $this->currentFile->setTo(trim($line, '+ '));
    }

    /**
     * @param string[] $context
     */
    protected function hunk(string $line, array $context): void
    {
        if ($this->currentHunk && $this->currentFile) {
            $this->currentFile->addHunk($this->currentHunk);
        }

        $oldStart = (int) ($context[2] ?? 0);
        $oldCount = (int) ($context[3] ?? 0);
        $newStart = (int) ($context[5] ?? 0);
        $newCount = (int) ($context[6] ?? 0);

        $this->oldCounter = $oldStart;
        $this->newCounter = $newStart;
        $this->deletedLines = 0;
        $this->addedLines = 0;
        $this->currentHunk = new Hunk($line, $oldStart, $oldCount, $newStart, $newCount);
    }

    /**
     * @param string[] $context
     */
    protected function deletedLine(string $line, array $context): void
    {
        if (!$this->currentHunk || !$this->currentFile) {
            return;
        }

        $oldNumber = $this->oldCounter + $this->deletedLines;
        $newNumber = $this->newCounter;

        $this->currentHunk->addLine(new Line($line, Line::TYPE_DELETE, $oldNumber, $newNumber));
        $this->currentFile->increaseDeletions();
        ++$this->deletedLines;
    }

    /**
     * @param string[] $context
     */
    protected function addedLine(string $line, array $context): void
    {
        if (!$this->currentHunk || !$this->currentFile) {
            return;
        }

        $oldNumber = $this->oldCounter;
        $newNumber = $this->newCounter + $this->addedLines;

        $this->currentHunk->addLine(new Line($line, Line::TYPE_ADD, $oldNumber, $newNumber));
        $this->currentFile->increaseAdditions();
        ++$this->addedLines;
    }

    protected function line(string $line): void
    {
        if (!$this->currentHunk || !$this->currentFile) {
            return;
        }

        $oldNumber = $this->oldCounter + $this->deletedLines;
        $newNumber = $this->newCounter + $this->addedLines;

        $this->currentHunk->addLine(new Line($line, Line::TYPE_NO_CHANGE, $oldNumber, $newNumber));
        ++$this->oldCounter;
        ++$this->newCounter;
    }

    protected function clearAccumulator(): void
    {
        if ($this->currentFile) {
            if ($this->currentHunk) {
                $this->currentFile->addHunk($this->currentHunk);
            }

            $this->files[] = $this->currentFile;
        }

        $this->currentFile = $this->currentHunk = null;
    }
}
