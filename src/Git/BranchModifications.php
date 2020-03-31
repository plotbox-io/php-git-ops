<?php

namespace App\Git;

use App\RelativeFile;

class BranchModifications
{
    /** @var RelativeFile[] */
    private $modifiedFiles;
    /** @var RelativeFile[] */
    private $newFiles;
    /** @var Commit[] */
    private $commits;
    /** @var UnstagedChanges */
    private $unstagedChanges;

    /**
     * @param RelativeFile[] $modifiedFiles
     * @param RelativeFile[] $newFiles
     * @param Commit[] $commits
     * @param UnstagedChanges $unstagedChanges
     */
    public function __construct(array $modifiedFiles, array $newFiles, array $commits, UnstagedChanges $unstagedChanges)
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

    public function filter(FileFilter $filter)
    {
        $this->modifiedFiles = $filter->getFilteredFiles($this->modifiedFiles);
        $this->newFiles = $filter->getFilteredFiles($this->newFiles);
        $this->unstagedChanges->filter($filter);
    }

    /**
     * Get the relative file paths for all files that have been modified (including additions,
     * 'unstaged new' files and #staged but uncommitted' files)
     *
     * @return RelativeFile[]
     */
    public function getModifiedFilePaths()
    {
        $modifiedFilesFromCommits = [];
        foreach ($this->modifiedFiles as $file) {
            $modifiedFilesFromCommits[] = $file->getPath();
        }

        return array_unique(
            array_merge(
                $modifiedFilesFromCommits,
                array_keys($this->newFiles),
                $this->unstagedChanges->getModifiedPaths()
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
     * @param Commit $commit
     * @return bool
     */
    public function commitPartOfModifications(Commit $commit)
    {
        return key_exists($commit->getHash(), $this->commits);
    }

    /**
     * @return UnstagedChanges
     */
    public function getUnstagedChanges()
    {
        return $this->unstagedChanges;
    }
}
