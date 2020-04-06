<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

class BranchModifications
{
    /** @var TouchedLines */
    private $modifiedFiles;
    /** @var RelativeFile[] */
    private $newFiles;
    /** @var Commit[] */
    private $commits;
    /** @var TouchedLines */
    private $unstagedChanges;
    /**
     * @param TouchedLines $modifiedFiles
     * @param RelativeFile[] $newFiles
     * @param Commit[] $commits
     * @param TouchedLines $unstagedChanges
     */
    public function __construct(TouchedLines $modifiedFiles, array $newFiles, array $commits, TouchedLines $unstagedChanges)
    {
        $this->modifiedFiles = $modifiedFiles;
        $this->newFiles = [];
        foreach ($newFiles as $newFile) {
            $this->newFiles[$newFile->getPath()] = $newFile;
        }
        $this->unstagedChanges = $unstagedChanges;
        $this->commits = [];
        foreach ($commits as $commit) {
            $this->commits[$commit->getHash()] = $commit;
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
        $this->unstagedChanges->filter($filter);
        $this->newFiles = $filter->getFilteredFiles($this->newFiles);
    }

    /**
     * Get the relative file paths for all files that have been modified (including additions,
     * 'unstaged new' files and #staged but uncommitted' files)
     *
     * @return RelativeFile[]
     */
    public function getModifiedFilePaths()
    {
        return array_unique(
            array_merge(
                $this->modifiedFiles->getModifiedPaths(),
                $this->unstagedChanges->getModifiedPaths(),
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
        return $this->unstagedChanges->wasModified($file, $line) || $this->modifiedFiles->wasModified($file, $line);
    }
}
