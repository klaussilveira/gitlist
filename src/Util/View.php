<?php

namespace GitList\Util;

class View
{
    /**
     * Builds a breadcrumb array based on a path spec.
     *
     * @param  string $spec Path spec
     *
     * @return array  Array with parts of the breadcrumb
     */
    public function getBreadcrumbs($spec)
    {
        if (!$spec) {
            return array();
        }

        $paths = explode('/', $spec);

        foreach ($paths as $i => $path) {
            $breadcrumbs[] = array(
                'dir' => $path,
                'path' => implode('/', array_slice($paths, 0, $i + 1)),
            );
        }

        return $breadcrumbs;
    }

    public function getPager($pageNumber, $totalCommits)
    {
        $pageNumber = (empty($pageNumber)) ? 0 : $pageNumber;
        $lastPage = (int) ($totalCommits / 15);

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
}
