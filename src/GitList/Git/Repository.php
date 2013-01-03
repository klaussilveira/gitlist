<?php

namespace GitList\Git;

use Gitter\Repository as BaseRepository;
use Gitter\Model\Commit\Commit;
use Gitter\PrettyFormat;
use Symfony\Component\Filesystem\Filesystem;

class Repository extends BaseRepository
{

    /**
     * Show the data from a specific commit
     *
     * @param  string $commitHash Hash of the specific commit to read data
     * @return array  Commit data
     */
    public function getCommit($commitHash)
    {
        $logs = $this->getClient()->run($this, "show --pretty=format:\"<item><hash>%H</hash><short_hash>%h</short_hash><tree>%T</tree><parents>%P</parents><author>%an</author><author_email>%ae</author_email><date>%at</date><commiter>%cn</commiter><commiter_email>%ce</commiter_email><commiter_date>%ct</commiter_date><message><![CDATA[%s]]></message></item>\" $commitHash");
        $logs = explode("\n", $logs);

        // Read commit metadata
        $format = new PrettyFormat;
        $data = $format->parse($logs[0]);
        $commit = new Commit;
        $commit->importData($data[0]);
        unset($logs[0]);

        if (empty($logs[1])) {
            $logs = explode("\n", $this->getClient()->run($this, 'diff ' . $commitHash . '~1..' . $commitHash));
        }

        $commit->setDiffs($this->readDiffLogs($logs));

        return $commit;
    }

    /**
     * Blames the provided file and parses the output
     *
     * @param  string $file File that will be blamed
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
                    'commitShort' => substr($currentCommit, 0, 8)
                );
            }

            $blame[$i]['line'] .= PHP_EOL . $match[3][0];
            $previousCommit = $currentCommit;
        }

        return $blame;
    }

    /**
     * Show the repository commit log with pagination
     *
     * @access public
     * @return array Commit log
     */
    public function getPaginatedCommits($file = null, $page = 0)
    {
        $page = 15 * $page;
        $pager = "--skip=$page --max-count=15";
        $command = "log $pager --pretty=format:\"<item><hash>%H</hash><short_hash>%h</short_hash><tree>%T</tree><parent>%P</parent><author>%an</author><author_email>%ae</author_email><date>%at</date><commiter>%cn</commiter><commiter_email>%ce</commiter_email><commiter_date>%ct</commiter_date><message><![CDATA[%s]]></message></item>\"";

        if ($file) {
            $command .= " $file";
        }

        try {
            $logs = $this->getPrettyFormat($command);
        } catch (\RuntimeException $e) {
            return array();
        }

        foreach ($logs as $log) {
            $commit = new Commit;
            $commit->importData($log);
            $commits[] = $commit;
        }

        return $commits;
    }

    public function searchCommitLog($query)
    {
        $query = escapeshellarg($query);
        $command = "log --grep={$query} --pretty=format:\"<item><hash>%H</hash><short_hash>%h</short_hash><tree>%T</tree><parent>%P</parent><author>%an</author><author_email>%ae</author_email><date>%at</date><commiter>%cn</commiter><commiter_email>%ce</commiter_email><commiter_date>%ct</commiter_date><message><![CDATA[%s]]></message></item>\"";

        try {
            $logs = $this->getPrettyFormat($command);
        } catch (\RuntimeException $e) {
            return array();
        }

        foreach ($logs as $log) {
            $commit = new Commit;
            $commit->importData($log);
            $commits[] = $commit;
        }

        return $commits;
    }

    public function searchTree($query, $branch)
    {
        $query = escapeshellarg($query);

        try {
            $results = $this->getClient()->run($this, "grep -I --line-number {$query} $branch");
        } catch (\RuntimeException $e) {
            return false;
        }

        $results = explode("\n", $results);

        foreach ($results as $result) {
            if ($result == '') {
                continue;
            }

            preg_match_all('/([\w-._]+):([^:]+):([0-9]+):(.+)/', $result, $matches, PREG_SET_ORDER);

            $data['branch'] = $matches[0][1];
            $data['file']   = $matches[0][2];
            $data['line']   = $matches[0][3];
            $data['match']  = $matches[0][4];

            $searchResults[] = $data;
        }

        return $searchResults;
    }

    public function getAuthorStatistics()
    {
        $logs = $this->getClient()->run($this, 'log --pretty=format:"%an||%ae" ' . $this->getHead());

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

    public function getStatistics($branch)
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

            if (($pos = strrpos($file[4], '.')) !== FALSE) {
                $data['extensions'][] = substr($file[4], $pos);
            }
        }

        $data['extensions'] = array_count_values($data['extensions']);
        arsort($data['extensions']);

        return $data;
    }

    /**
     * Create a TAR or ZIP archive of a git tree
     *
     * @param string $tree   Tree-ish reference
     * @param string $output Output File name
     * @param string $format Archive format
     */
    public function createArchive($tree, $output, $format = 'zip')
    {
        $fs = new Filesystem;
        $fs->mkdir(dirname($output));
        $this->getClient()->run($this, "archive --format=$format --output=$output $tree");
    }
}
