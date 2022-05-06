<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

class TouchedLines
{
    /** @var string[][] */
    private $changes = [];

    /**
     * @param int $firstLineInclusive
     * @param int $lastLineInclusive
     * @return void
     */
    public function addTouchedLines(RelativeFile $file, $firstLineInclusive, $lastLineInclusive)
    {
        $path = $file->getPath();
        if (!isset($this->changes[$path])) {
            $this->changes[$path] = [];
        }

        $this->changes[$path][] = "$firstLineInclusive-$lastLineInclusive";
    }

    /**
     * @return string[]
     */
    public function getModifiedPaths()
    {
        return array_keys($this->changes);
    }

    /** @return array<string, string[]> */
    public function getAllTouchedLines()
    {
        return $this->changes;
    }

    /**
     * @param RelativeFile $file
     * @param int $lineNumber
     * @return bool
     */
    public function wasModified(RelativeFile $file, $lineNumber)
    {
        $path = $file->getPath();

        if (!isset($this->changes[$path])) {
            return false;
        }

        foreach ($this->changes[$path] as $lineChanges) {
            list($firstLine, $lastLine) = explode('-', $lineChanges);
            if ($lineNumber >= $firstLine && $lineNumber <= $lastLine) {
                return true;
            }
        }

        return false;
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

       foreach (array_keys($this->changes) as $path) {
            if (!array_key_exists($path, $filteredIndex)) {
                unset($this->changes[$path]);
            }
        }
    }
}
