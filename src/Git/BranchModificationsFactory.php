<?php

namespace App\Git;

use App\RelativeFile;

class BranchModificationsFactory
{
    /** @var Git */
    private $git;
    /** @var BranchModifications[] */
    private $branchModificationsCached;

    public function __construct(Git $git)
    {
        $this->git = $git;
        $this->branchModificationsCached = [];
    }

    /**
     * @param Commit $mergeBase
     * @param Commit $current
     * @param RelativeFile|null $singleTargetFile
     * @return BranchModifications
     */
    public function getBranchModifications(Commit $mergeBase, Commit $current, RelativeFile $singleTargetFile = null)
    {
        $cacheKey = $mergeBase->getHash() . '~' . $current->getHash();
        if (isset($this->branchModificationsCached[$cacheKey])) {
            return $this->branchModificationsCached[$cacheKey];
        }

        $modifiedFiles = $singleTargetFile ? [$singleTargetFile] : $this->git->getModifiedFiles($mergeBase, $current);
        $newFiles = $this->git->getNewlyAddedFiles($mergeBase, $current);
        $commitList = $this->git->getCommitsBetween($mergeBase, $current);
        $unstagedChanges = $this->git->getUnstagedChanges();

        $this->branchModificationsCached[$cacheKey] = new BranchModifications(
            $modifiedFiles,
            $newFiles,
            $commitList,
            $unstagedChanges
        );

        return $this->branchModificationsCached[$cacheKey];
    }
}
