<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

class BranchModifications
{
    /** @var TouchedLines */
    private $modifiedFiles;
    /** @var RelativeFile[] */
    private $newFiles;

    /**
     * @param TouchedLines $modifiedFiles
     * @param RelativeFile[] $newFiles
     */
    public function __construct(TouchedLines $modifiedFiles, array $newFiles)
    {
        $this->modifiedFiles = $modifiedFiles;
        $this->newFiles = [];
        foreach ($newFiles as $newFile) {
            $this->newFiles[$newFile->getPath()] = $newFile;
        }
    }

    /**
     * Filter out unwanted files according to some custom filter
     *
     * @param FileFilter $filter
     */
    public function filter(FileFilter $filter)
    {
        $this->modifiedFiles->filter($filter);
        $this->newFiles = $filter->getFilteredFiles($this->newFiles);
    }

    /**
     * Get the relative file paths for all files that have been modified (including additions,
     * 'unstaged new' files and #staged but uncommitted' files)
     *
     * @return string[]
     */
    public function getModifiedFilePaths()
    {
        return array_unique(
            array_merge(
                $this->modifiedFiles->getModifiedPaths(),
                array_keys($this->newFiles)
            )
        );
    }

    /**
     * @param RelativeFile $file
     * @return bool
     */
    public function isNewFile(RelativeFile $file)
    {
        return array_key_exists($file->getPath(), $this->newFiles);
    }

    /**
     * @param $file
     * @param $line
     * @return bool
     */
    public function wasModified($file, $line)
    {
        return $this->modifiedFiles->wasModified($file, $line);
    }
}
