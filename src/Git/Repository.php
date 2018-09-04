<?php

namespace GitList\Git;

use Gitter\Model\Commit\Commit;
use Gitter\Model\Commit\Diff;
use Gitter\PrettyFormat;
use Gitter\Repository as BaseRepository;
use Symfony\Component\Filesystem\Filesystem;

class Repository extends BaseRepository
{
    /**
     * Return true if the repo contains this commit.
     *
     * @param string $commitHash Hash of commit whose existence we want to check
     *
     * @return bool Whether or not the commit exists in this repo
     */
    public function hasCommit($commitHash)
    {
        $logs = $this->getClient()->run($this, "show $commitHash");
        $logs = explode("\n", $logs);

        return strpos($logs[0], 'commit') === 0;
    }

    /**
     * Get the current branch, returning a default value when HEAD is detached.
     */
    public function getHead($default = null)
    {
        $client = $this->getClient();

        return parent::getHead($client->getDefaultBranch());
    }

    /**
     * Show Patches that where apllied to the selected file.
     *
     * @param  string $file File path for which we will retrieve a list of patch logs
     *
     * @return array  Collection of Commits data
     */
    public function getCommitsLogPatch($file)
    {
        $record_delimiter = chr(hexdec('0x1e'));
        $file_patches = $this->getClient()->run(
            $this,
            'log -p --pretty=format:"' . $record_delimiter . '<item><hash>%H</hash>'
            . '<short_hash>%h</short_hash><tree>%T</tree><parents>%P</parents>'
            . '<author>%aN</author><author_email>%aE</author_email>'
            . '<date>%at</date><commiter>%cN</commiter><commiter_email>%cE</commiter_email>'
            . '<commiter_date>%ct</commiter_date>'
            . '<message><![CDATA[%s]]></message>'
            . '<body><![CDATA[%b]]></body>'
            . "</item>\" -- $file"
        );

        $patch_collection = array();
        foreach (preg_split('/(' . $record_delimiter . '\<item\>)/', $file_patches, null, PREG_SPLIT_NO_EMPTY) as $patches) {
            $patches = '<item>' . $patches;
            $xmlEnd = strpos($patches, '</item>') + 7;
            $commitInfo = substr($patches, 0, $xmlEnd);
            $commitData = substr($patches, $xmlEnd);
            $logs = explode("\n", $commitData);

            // Read commit metadata
            $format = new PrettyFormat();
            $data = $format->parse($commitInfo);
            $commit = new Commit();
            $commit->importData($data[0]);
            $commit->setDiffs($this->readDiffLogs($logs));
            $patch_collection[] = $commit;
        }

        return $patch_collection;
    }

    /**
     * Show the data from a specific commit.
     *
     * @param  string $commitHash Hash of the specific commit to read data
     *
     * @return array  Commit data
     */
    public function getCommit($commitHash)
    {
        $logs = $this->getClient()->run(
            $this,
                  'show --pretty=format:"<item><hash>%H</hash>'
                . '<short_hash>%h</short_hash><tree>%T</tree><parents>%P</parents>'
                . '<author>%aN</author><author_email>%aE</author_email>'
                . '<date>%at</date><commiter>%cN</commiter><commiter_email>%cE</commiter_email>'
                . '<commiter_date>%ct</commiter_date>'
                . '<message><![CDATA[%s]]></message>'
                . '<body><![CDATA[%b]]></body>'
                . "</item>\" $commitHash"
        );

        $xmlEnd = strpos($logs, '</item>') + 7;
        $commitInfo = substr($logs, 0, $xmlEnd);
        $commitData = substr($logs, $xmlEnd);
        $logs = explode("\n", $commitData);

        // Read commit metadata
        $format = new PrettyFormat();
        $data = $format->parse($commitInfo);
        $commit = new Commit();
        $commit->importData($data[0]);

        if ($commit->getParentsHash()) {
            $command = 'diff ' . $commitHash . '~1..' . $commitHash;
            $logs = explode("\n", $this->getClient()->run($this, $command));
        }

        $commit->setDiffs($this->readDiffLogs($logs));

        return $commit;
    }

    /**
     * Blames the provided file and parses the output.
     *
     * @param  string $file File that will be blamed
     *
     * @return array  Commits hashes containing the lines
     */
    public function getBlame($file)
    {
        $blame = array();
        $logs = $this->getClient()->run($this, "blame --root -sl $file");
        $logs = explode("\n", $logs);

        $i = 0;
        $previousCommit = '';
        foreach ($logs as $log) {
            if ($log == '') {
                continue;
            }

            preg_match_all("/([a-zA-Z0-9]{40})\s+.*?([0-9]+)\)(.+)/", $log, $match);

            $currentCommit = $match[1][0];
            if ($currentCommit != $previousCommit) {
                ++$i;
                $blame[$i] = array(
                    'line' => '',
                    'commit' => $currentCommit,
                    'commitShort' => substr($currentCommit, 0, 8),
                );
            }

            $blame[$i]['line'] .= $match[3][0] . PHP_EOL;
            $previousCommit = $currentCommit;
        }

        return $blame;
    }

    /**
     * Read diff logs and generate a collection of diffs.
     *
     * @param  array $logs Array of log rows
     *
     * @return array Array of diffs
     */
    public function readDiffLogs(array $logs)
    {
        $diffs = array();
        $lineNumOld = 0;
        $lineNumNew = 0;
        foreach ($logs as $log) {
            // Skip empty lines
            if ($log == '') {
                continue;
            }

            if ('diff' === substr($log, 0, 4)) {
                if (isset($diff)) {
                    $diffs[] = $diff;
                }

                $diff = new Diff();
                if (preg_match('/^diff --[\S]+ a\/?(.+) b\/?/', $log, $name)) {
                    $diff->setFile($name[1]);
                }
                continue;
            }

            if ('index' === substr($log, 0, 5)) {
                $diff->setIndex($log);
                continue;
            }

            if ('---' === substr($log, 0, 3)) {
                $diff->setOld($log);
                continue;
            }

            if ('+++' === substr($log, 0, 3)) {
                $diff->setNew($log);
                continue;
            }

            // Handle binary files properly.
            if ('Binary' === substr($log, 0, 6)) {
                $m = array();
                if (preg_match('/Binary files (.+) and (.+) differ/', $log, $m)) {
                    $diff->setOld($m[1]);
                    $diff->setNew("    {$m[2]}");
                }
            }

            if (!empty($log)) {
                switch ($log[0]) {
                    case '@':
                        // Set the line numbers
                        preg_match('/@@ -([0-9]+)(?:,[0-9]+)? \+([0-9]+)/', $log, $matches);
                        $lineNumOld = $matches[1] - 1;
                        $lineNumNew = $matches[2] - 1;
                        break;
                    case '-':
                        $lineNumOld++;
                        break;
                    case '+':
                        $lineNumNew++;
                        break;
                    default:
                        $lineNumOld++;
                        $lineNumNew++;
                }
            } else {
                $lineNumOld++;
                $lineNumNew++;
            }

            if (isset($diff)) {
                $diff->addLine($log, $lineNumOld, $lineNumNew);
            }
        }

        if (isset($diff)) {
            $diffs[] = $diff;
        }

        return $diffs;
    }

    /**
     * Show the repository commit log with pagination.
     *
     * @param string $file
     * @param int $page
     *
     * @return array Commit log
     */
    public function getPaginatedCommits($file = null, $page = 0)
    {
        $page = 15 * $page;
        $pager = "--skip=$page --max-count=15";
        $command =
                  "log $pager --pretty=format:\"<item><hash>%H</hash>"
                . '<short_hash>%h</short_hash><tree>%T</tree><parents>%P</parents>'
                . '<author>%aN</author><author_email>%aE</author_email>'
                . '<date>%at</date><commiter>%cN</commiter>'
                . '<commiter_email>%cE</commiter_email>'
                . '<commiter_date>%ct</commiter_date>'
                . '<message><![CDATA[%s]]></message></item>"';

        if ($file) {
            $command .= " $file";
        }

        try {
            $logs = $this->getPrettyFormat($command);
        } catch (\RuntimeException $e) {
            return array();
        }

        foreach ($logs as $log) {
            $commit = new Commit();
            $commit->importData($log);
            $commits[] = $commit;
        }

        return $commits;
    }

    public function searchCommitLog($query, $branch)
    {
        $query = escapeshellarg($query);
        $query = strtr($query, array('[' => '\\[', ']' => '\\]'));
        $command =
              "log --grep={$query} -i --pretty=format:\"<item><hash>%H</hash>"
            . '<short_hash>%h</short_hash><tree>%T</tree><parents>%P</parents>'
            . '<author>%aN</author><author_email>%aE</author_email>'
            . '<date>%at</date><commiter>%cN</commiter>'
            . '<commiter_email>%cE</commiter_email>'
            . '<commiter_date>%ct</commiter_date>'
            . '<message><![CDATA[%s]]></message></item>"'
            . " $branch";

        try {
            $logs = $this->getPrettyFormat($command);
        } catch (\RuntimeException $e) {
            return array();
        }

        foreach ($logs as $log) {
            $commit = new Commit();
            $commit->importData($log);
            $commits[] = $commit;
        }

        return $commits;
    }

    public function searchTree($query, $branch)
    {
        if (empty($query)) {
            return null;
        }

        $query = preg_replace('/(--?[A-Za-z0-9\-]+)/', '', $query);
        $query = escapeshellarg($query);

        try {
            $results = $this->getClient()->run($this, "grep -i --line-number -- {$query} $branch");
        } catch (\RuntimeException $e) {
            return false;
        }

        $results = explode("\n", $results);
        $searchResults = array();

        foreach ($results as $result) {
            if ($result == '') {
                continue;
            }

            preg_match_all('/([\w-._]+):([^:]+):([0-9]+):(.+)/', $result, $matches, PREG_SET_ORDER);

            $data['branch'] = $matches[0][1];
            $data['file'] = $matches[0][2];
            $data['line'] = $matches[0][3];
            $data['match'] = $matches[0][4];

            $searchResults[] = $data;
        }

        return $searchResults;
    }

    public function getAuthorStatistics($branch)
    {
        $logs = $this->getClient()->run($this, 'log --pretty=format:"%aN||%aE" ' . $branch);

        if (empty($logs)) {
            throw new \RuntimeException('No statistics available');
        }

        $logs = explode("\n", $logs);
        $logs = array_count_values($logs);
        arsort($logs);

        foreach ($logs as $user => $count) {
            $user = explode('||', $user);
            $data[] = array('name' => $user[0], 'email' => $user[1], 'commits' => $count);
        }

        return $data;
    }

    public function getBranchStatistics($branch)
    {
        // Calculate amount of files, extensions and file size
        $logs = $this->getClient()->run($this, 'ls-tree -r -l ' . $branch);
        $lines = explode("\n", $logs);
        $files = array();
        $data['extensions'] = array();
        $data['size'] = 0;
        $data['files'] = 0;

        foreach ($lines as $key => $line) {
            if (empty($line)) {
                unset($lines[$key]);
                continue;
            }

            $files[] = preg_split("/[\s]+/", $line);
        }

        foreach ($files as $file) {
            if ($file[1] == 'blob') {
                $data['files']++;
            }

            if (is_numeric($file[3])) {
                $data['size'] += $file[3];
            }
        }

        $logs = $this->getClient()->run($this, 'ls-tree -l -r --name-only ' . $branch);
        $files = explode("\n", $logs);
        foreach ($files as $file) {
            if (($pos = strrpos($file, '.')) !== false) {
                $extension = substr($file, $pos);

                if (($pos = strrpos($extension, '/')) === false) {
                    $data['extensions'][] = $extension;
                }
            }
        }

        $data['extensions'] = array_count_values($data['extensions']);
        arsort($data['extensions']);

        return $data;
    }

    /**
     * Create a TAR or ZIP archive of a git tree.
     *
     * @param string $tree   Tree-ish reference
     * @param string $output Output File name
     * @param string $format Archive format
     */
    public function createArchive($tree, $output, $format = 'zip')
    {
        $fs = new Filesystem();
        $fs->mkdir(dirname($output));
        $this->getClient()->run($this, "archive --format=$format --output='$output' $tree");
    }

    /**
     * Return true if $path exists in $branch; return false otherwise.
     *
     * @param string $commitish commitish reference; branch, tag, SHA1, etc
     * @param string $path      path whose existence we want to verify
     *
     * @return bool
     *
     * GRIPE Arguably belongs in Gitter, as it's generally useful functionality.
     * Also, this really may not be the best way to do this.
     */
    public function pathExists($commitish, $path)
    {
        $output = $this->getClient()->run($this, "ls-tree $commitish '$path'");

        if (strlen($output) > 0) {
            return true;
        }

        return false;
    }
}
