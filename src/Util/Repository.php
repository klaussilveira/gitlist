<?php

namespace GitList\Util;

use Silex\Application;

class Repository
{
    protected $app;

    protected $defaultFileTypes = array(
        'php' => 'php',
        'c' => 'clike',
        'h' => 'clike',
        'cpp' => 'clike',
        'm' => 'clike',
        'mm' => 'clike',
        'ino' => 'clike',
        'cs' => 'text/x-csharp',
        'java' => 'text/x-java',
        'clj' => 'clojure',
        'coffee' => 'coffeescript',
        'css' => 'css',
        'diff' => 'diff',
        'ecl' => 'ecl',
        'el' => 'erlang',
        'go' => 'go',
        'groovy' => 'groovy',
        'hs' => 'haskell',
        'lhs' => 'haskell',
        'jsp' => 'application/x-jsp',
        'asp' => 'htmlembedded',
        'aspx' => 'htmlembedded',
        'html' => 'htmlmixed',
        'tpl' => 'htmlmixed',
        'js' => 'javascript',
        'json' => 'javascript',
        'less' => 'less',
        'lua' => 'lua',
        'md' => 'markdown',
        'markdown' => 'markdown',
        'sql' => 'mysql',
        'ml' => 'ocaml',
        'mli' => 'ocaml',
        'pl' => 'perl',
        'pm' => 'perl',
        'pas' => 'pascal',
        'ini' => 'properties',
        'cfg' => 'properties',
        'nt' => 'ntriples',
        'py' => 'python',
        'rb' => 'ruby',
        'rst' => 'rst',
        'r' => 'r',
        'sh' => 'shell',
        'ss' => 'scheme',
        'scala' => 'text/x-scala',
        'scm' => 'scheme',
        'sls' => 'scheme',
        'sps' => 'scheme',
        'rs' => 'rust',
        'st' => 'smalltalk',
        'tex' => 'stex',
        'vbs' => 'vbscript',
        'vb' => 'vbscript',
        'v' => 'verilog',
        'xml' => 'xml',
        'xsd' => 'xml',
        'xsl' => 'xml',
        'xul' => 'xml',
        'xlf' => 'xml',
        'xliff' => 'xml',
        'xaml' => 'xml',
        'wxs' => 'xml',
        'wxl' => 'xml',
        'wxi' => 'xml',
        'wsdl' => 'xml',
        'svg' => 'xml',
        'rss' => 'xml',
        'rdf' => 'xml',
        'plist' => 'xml',
        'mxml' => 'xml',
        'kml' => 'xml',
        'glade' => 'xml',
        'xq' => 'xquery',
        'xqm' => 'xquery',
        'xquery' => 'xquery',
        'xqy' => 'xquery',
        'yml' => 'yaml',
        'yaml' => 'yaml',
        'png' => 'image',
        'jpg' => 'image',
        'gif' => 'image',
        'jpeg' => 'image',
        'bmp' => 'image',
        'csproj' => 'xml',
    );

    protected static $binaryTypes = array(
        'exe', 'com', 'so', 'la', 'o', 'dll', 'pyc',
        'jpg', 'jpeg', 'bmp', 'gif', 'png', 'xmp', 'pcx', 'svgz', 'ttf', 'tiff', 'oet',
        'gz', 'tar', 'rar', 'zip', '7z', 'jar', 'class',
        'odt', 'ods', 'pdf', 'doc', 'docx', 'dot', 'xls', 'xlsx',
    );

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Returns the file type based on filename by treating the extension.
     *
     * The file type is used by CodeMirror, a Javascript-based IDE implemented in
     * GitList, to properly highlight the blob syntax (if it's a source-code)
     *
     * @param  string $file File name
     *
     * @return mixed  File type
     */
    public function getFileType($file)
    {
        if (($pos = strrpos($file, '.')) !== false) {
            $fileType = substr($file, $pos + 1);
        } else {
            return 'text';
        }

        if (!empty($this->app['filetypes'])) {
            if (isset($this->app['filetypes'][$fileType])) {
                return $this->app['filetypes'][$fileType];
            }
        }

        if (isset($this->defaultFileTypes[$fileType])) {
            return $this->defaultFileTypes[$fileType];
        }

        return 'text';
    }

    /**
     * Returns whether the file is binary.
     *
     * @param string $file
     *
     * @return bool
     */
    public function isBinary($file)
    {
        if (($pos = strrpos($file, '.')) !== false) {
            $fileType = substr($file, $pos + 1);
        } else {
            return false;
        }

        if (!empty($this->app['binary_filetypes']) && array_key_exists($fileType, $this->app['binary_filetypes'])) {
            return $this->app['binary_filetypes'][$fileType];
        }

        if (in_array($fileType, self::$binaryTypes)) {
            return true;
        }

        return false;
    }

    public function getReadme($repository, $branch = null, $path = '')
    {
        if ($branch === null) {
            $branch = $repository->getHead();
        }

        if ($path != '') {
            $path = "$path/";
        }

        $files = $repository->getTree($path != '' ? "$branch:\"$path\"" : $branch)->output();

        foreach ($files as $file) {
            if (preg_match('/^readme*/i', $file['name'])) {
                return array(
                    'filename' => $file['name'],
                    'content' => $repository->getBlob("$branch:\"$path{$file['name']}\"")->output(),
                );
            }
        }
        // No contextual readme, try to catch the main one if we are in deeper context
        if ($path != '') {
            return $this->getReadme($repository, $branch, '');
        }

        return array();
    }

    /**
     * Returns an Array where the first value is the tree-ish and the second is the path.
     *
     * @param  \GitList\Git\Repository $repository
     * @param  string                  $branch
     * @param  string                  $tree
     *
     * @return array
     */
    public function extractRef($repository, $branch = '', $tree = '')
    {
        $branch = trim($branch, '/');
        $tree = trim($tree, '/');
        $input = $branch . '/' . $tree;

        // If the ref appears to be a SHA, just split the string
        if (preg_match('/^([[:alnum:]]{40})(.+)/', $input, $matches)) {
            $branch = $matches[1];
        } else {
            // Otherwise, attempt to detect the ref using a list of the project's branches and tags
            $validRefs = array_merge((array) $repository->getBranches(), (array) $repository->getTags());
            foreach ($validRefs as $key => $ref) {
                if (!preg_match(sprintf('#^%s/#', preg_quote($ref, '#')), $input)) {
                    unset($validRefs[$key]);
                }
            }

            // No exact ref match, so just try our best
            if (count($validRefs) > 1) {
                preg_match('/([^\/]+)(.*)/', $input, $matches);
                $branch = preg_replace('/^\/|\/$/', '', $matches[1]);
            } else {
                // Extract branch name
                $branch = array_shift($validRefs);
            }
        }

        return array($branch, $tree);
    }
}
