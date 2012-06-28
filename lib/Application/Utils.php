<?php

namespace Application;

use Silex\Application;

/**
 * General helper class, mostly used for string parsing inside the application controllers
 */
class Utils
{
    protected $app;

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
        $paths = explode('/', $spec);
        $last = '';

        foreach ($paths as $path) {
            $dir['dir'] = $path;
            $dir['path'] = "$last/$path";
            $breadcrumbs[] = $dir;
            $last .= '/' . $path;
        }

        if (isset($paths[2])) {
            $breadcrumbs[0]['path'] .= '/' . $paths[1] . '/' . $paths[2];
        }
        
        unset($breadcrumbs[1], $breadcrumbs[2]);
        return $breadcrumbs;
    }

    /**
     * Returns the file type based on filename by treating the extension
     *
     * The file type is used by CodeMirror, a Javascript-based IDE implemented in
     * GitList, to properly highlight the blob syntax (if it's a source-code)
     * 
     * @param string $spec File name
     * @return string File type
     */
    public function getFileType($file)
    {
        if (($pos = strrpos($file, '.')) !== FALSE) {
            $fileType = substr($file, $pos + 1);
        } else {
            return 'text';
        }

        switch ($fileType) {
            case 'php':
                return 'php';
            case 'c':
                return 'clike';
            case 'h':
                return 'clike';
            case 'cpp':
                return 'clike';
            case 'cs':
                return 'csharp';
            case 'm':
                return 'clike';
            case 'mm':
                return 'clike';
            case 'java':
                return 'java';
            case 'clj':
                return 'clojure';
            case 'coffee':
                return 'coffeescript';
            case 'css':
                return 'css';
            case 'diff':
                return 'diff';
            case 'ecl':
                return 'ecl';
            case 'el':
                return 'erlang';
            case 'go':
                return 'go';
            case 'groovy':
                return 'groovy';
            case 'hs':
                return 'haskell';
            case 'lhs':
                return 'haskell';
            case 'jsp':
                return 'htmlembedded';
            case 'asp':
                return 'htmlembedded';
            case 'aspx':
                return 'htmlembedded';
            case 'html':
                return 'htmlmixed';
            case 'tpl':
                return 'htmlmixed';
            case 'js':
                return 'javascript';
            case 'json':
                return 'javascript';
            case 'less':
                return 'less';
            case 'lua':
                return 'lua';
            case 'md':
                return 'markdown';
            case 'markdown':
                return 'markdown';
            case 'sql':
                return 'mysql';
            case 'pl':
                return 'perl';
            case 'pm':
                return 'perl';
            case 'pas':
                return 'pascal';
            case 'ini':
                return 'properties';
            case 'cfg':
                return 'properties';
            case 'nt':
                return 'ntriples';
            case 'py':
                return 'python';
            case 'rb':
                return 'ruby';
            case 'rst':
                return 'rst';
            case 'r':
                return 'r';
            case 'sh':
                return 'shell';
            case 'ss':
                return 'scheme';
            case 'scm':
                return 'scheme';
            case 'sls':
                return 'scheme';
            case 'sps':
                return 'scheme';
            case 'rs':
                return 'rust';
            case 'st':
                return 'smalltalk';
            case 'tex':
                return 'stex';
            case 'vbs':
                return 'vbscript';
            case 'v':
                return 'verilog';
            case 'xml':
                return 'xml';
            case 'xsd':
                return 'xml';
            case 'xsl':
                return 'xml';
            case 'xul':
                return 'xml';
            case 'xlf':
                return 'xml';
            case 'xliff':
                return 'xml';
            case 'xaml':
                return 'xml';
            case 'wxs':
                return 'xml';
            case 'wxl':
                return 'xml';
            case 'wxi':
                return 'xml';
            case 'wsdl':
                return 'xml';
            case 'svg':
                return 'xml';
            case 'rss':
                return 'xml';
            case 'rdf':
                return 'xml';
            case 'plist':
                return 'xml';
            case 'mxml':
                return 'xml';
            case 'kml':
                return 'xml';
            case 'glade':
                return 'xml';
            case 'xq':
                return 'xquery';
            case 'xqm':
                return 'xquery';
            case 'xquery':
                return 'xquery';
            case 'xqy':
                return 'xquery';
            case 'yml':
                return 'yaml';
            case 'yaml':
                return 'yaml';
            case 'png':
                return 'image';
            case 'jpg':
                return 'image';
            case 'gif':
                return 'image';
            case 'jpeg':
                return 'image';
            case 'bmp':
                return 'image';
        }

        if (!empty($this->app['filetypes'])) {
            foreach ($this->app['filetypes'] as $ext => $type) {
                if ($fileType == $ext) {
                    return $type;
                }
            }
        }
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
                return array('filename' => $fileInfo['name'], 'content' => $repository->getBlob("$branch:'".$fileInfo['name']."'")->output());
            }
        return array();
    }
}
