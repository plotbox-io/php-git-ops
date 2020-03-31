<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

/**
 * Value object holding data on the lines that have been touched that
 * are currently unstaged
 */
class UnstagedChanges
{
    /** @var int[][] */
    private $changes = [];

    /**
     * @param RelativeFile $file
     * @param int $lineNumber
     */
    public function addTouchedLine(RelativeFile $file, $lineNumber)
    {
        $path = $file->getPath();
        if (!isset($this->changes[$path])) {
            $this->changes[$path] = [];
        }

        $this->changes[$path][$lineNumber] = $lineNumber;
    }

    /**
     * @return string[]
     */
    public function getModifiedPaths()
    {
        return array_keys($this->changes);
    }

    /**
     * @param RelativeFile $file
     * @param int $lineNumber
     * @return bool
     */
    public function wasModified(RelativeFile $file, $lineNumber)
    {
        $path = $file->getPath();

        return isset($this->changes[$path]) && key_exists($lineNumber, $this->changes[$path]);
    }

    public function filter(FileFilter $filter)
    {
        $allFiles = [];
        foreach (array_keys($this->changes) as $path) {
            $allFiles[] = new RelativeFile($path);
        }

        $filteredFiles = $filter->getFilteredFiles($allFiles);
        $filteredIndex = [];
        foreach ($filteredFiles as $filteredFile) {
            $filteredIndex[$filteredFile->getPath()] = null;
        }

        foreach ($this->changes as $path => $lines) {
            if (!array_key_exists($path, $filteredIndex)) {
                unset($this->changes[$path]);
            }
        }
    }
}
