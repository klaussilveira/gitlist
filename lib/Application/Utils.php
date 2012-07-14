<?php

namespace Application;

use Silex\Application;

/**
 * General helper class, mostly used for string parsing inside the application controllers
 */
class Utils
{
    protected $app;

    protected $defaultFileTypes = array(
        'php'      => 'php',
        'c'        => 'clike',
        'h'        => 'clike',
        'cpp'      => 'clike',
        'm'        => 'clike',
        'mm'       => 'clike',
        'cs'       => 'csharp',
        'java'     => 'java',
        'clj'      => 'clojure',
        'coffee'   => 'coffeescript',
        'css'      => 'css',
        'diff'     => 'diff',
        'ecl'      => 'ecl',
        'el'       => 'erlang',
        'go'       => 'go',
        'groovy'   => 'groovy',
        'hs'       => 'haskell',
        'lhs'      => 'haskell',
        'jsp'      => 'htmlembedded',
        'asp'      => 'htmlembedded',
        'aspx'     => 'htmlembedded',
        'html'     => 'htmlmixed',
        'tpl'      => 'htmlmixed',
        'js'       => 'javascript',
        'json'     => 'javascript',
        'less'     => 'less',
        'lua'      => 'lua',
        'md'       => 'markdown',
        'markdown' => 'markdown',
        'sql'      => 'mysql',
        'pl'       => 'perl',
        'pm'       => 'perl',
        'pas'      => 'pascal',
        'ini'      => 'properties',
        'cfg'      => 'properties',
        'nt'       => 'ntriples',
        'py'       => 'python',
        'rb'       => 'ruby',
        'rst'      => 'rst',
        'r'        => 'r',
        'sh'       => 'shell',
        'ss'       => 'scheme',
        'scm'      => 'scheme',
        'sls'      => 'scheme',
        'sps'      => 'scheme',
        'rs'       => 'rust',
        'st'       => 'smalltalk',
        'tex'      => 'stex',
        'vbs'      => 'vbscript',
        'v'        => 'verilog',
        'xml'      => 'xml',
        'xsd'      => 'xml',
        'xsl'      => 'xml',
        'xul'      => 'xml',
        'xlf'      => 'xml',
        'xliff'    => 'xml',
        'xaml'     => 'xml',
        'wxs'      => 'xml',
        'wxl'      => 'xml',
        'wxi'      => 'xml',
        'wsdl'     => 'xml',
        'svg'      => 'xml',
        'rss'      => 'xml',
        'rdf'      => 'xml',
        'plist'    => 'xml',
        'mxml'     => 'xml',
        'kml'      => 'xml',
        'glade'    => 'xml',
        'xq'       => 'xquery',
        'xqm'      => 'xquery',
        'xquery'   => 'xquery',
        'xqy'      => 'xquery',
        'yml'      => 'yaml',
        'yaml'     => 'yaml',
        'png'      => 'image',
        'jpg'      => 'image',
        'gif'      => 'image',
        'jpeg'     => 'image',
        'bmp'      => 'image',
    );

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Builds a breadcrumb array based on a path spec
     *
     * @param string $spec Path spec
     * @return array Array with parts of the breadcrumb
     */
    public function getBreadcrumbs($spec)
    {
        if (!$spec) {
            return array();
        }

        $paths = explode('/', $spec);

        foreach ($paths as $i => $path) {
            $breadcrumbs[] = array(
                'dir'  => $path,
                'path' => implode('/', array_slice($paths, 0, $i + 1)),
            );
        }

        return $breadcrumbs;
    }

    /**
     * Returns the file type based on filename by treating the extension
     *
     * The file type is used by CodeMirror, a Javascript-based IDE implemented in
     * GitList, to properly highlight the blob syntax (if it's a source-code)
     *
     * @param string $file File name
     *
     * @return null|string File type
     */
    public function getFileType($file)
    {
        if (($pos = strrpos($file, '.')) !== FALSE) {
            $fileType = substr($file, $pos + 1);
        } else {
            return 'text';
        }

        if (isset($this->defaultFileTypes[$fileType])) {
            return $this->defaultFileTypes[$fileType];
        }

        if (!empty($this->app['filetypes'])) {
            if (isset($this->app['filetypes'][$fileType])) {
                return $this->app['filetypes'][$fileType];
            }
        }

        return null;
    }

    public function getPager($pageNumber, $totalCommits)
    {
        $pageNumber = (empty($pageNumber)) ? 0 : $pageNumber;
        $lastPage = intval($totalCommits / 15);
        // If total commits are integral multiple of 15, the lastPage will be commits/15 - 1.
        $lastPage = ($lastPage * 15 == $totalCommits) ? $lastPage - 1 : $lastPage;
        $nextPage = $pageNumber + 1;
        $previousPage = $pageNumber - 1;

        return array('current' => $pageNumber,
                     'next' => $nextPage,
                     'previous' => $previousPage,
                     'last' => $lastPage,
                     'total' => $totalCommits,
        );
    }

    public function getReadme($repo, $branch = 'master')
    {
        $repository = $this->app['git']->getRepository($this->app['git.repos'] . $repo);
        $files = $repository->getTree($branch)->output();

        foreach ($files as $fileInfo)
            if (preg_match('/^readme*/i', $fileInfo['name'])) {
                return array(
                    'filename' => $fileInfo['name'],
                    'content'  => $repository->getBlob("$branch:'".$fileInfo['name']."'")->output()
                );
            }
        return array();
    }
}
