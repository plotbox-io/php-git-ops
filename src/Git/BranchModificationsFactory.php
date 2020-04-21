<?php

namespace PlotBox\PhpGitOps\Git;

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
     * Get modifications for the current branch (auto-detect parent)
     *
     * @return BranchModifications
     */
    public function getBranchModifications()
    {
        $branchComparer = new BranchComparer($this->git);
        $currentBranch = $this->git->getCurrentBranch();
        $ancestorBranch = $branchComparer->getAncestorBranch($currentBranch);
        return $this->getBranchModificationsSpecified(
            $ancestorBranch,
            $this->git->getLatestCommit($currentBranch)
        );
    }

    /**
     * @param Pointer $ancestorBranch
     * @param Pointer $current
     * @return BranchModifications
     */
    public function getBranchModificationsSpecified(Pointer $ancestorBranch, Pointer $current)
    {
        $mergeBase = $this->git->getMergeBase($ancestorBranch, $current);
        $cacheKey = $mergeBase->getName() . '~' . $current->getName();
        if (isset($this->branchModificationsCached[$cacheKey])) {
            return $this->branchModificationsCached[$cacheKey];
        }
        $modifiedFiles = $this->git->parseTouchedLines(
            "git diff --unified=0 {$mergeBase->getName()}"
        );
        $newFiles = $this->git->getNewlyAddedFiles($mergeBase, $current);
        $this->branchModificationsCached[$cacheKey] = new BranchModifications(
            $ancestorBranch,
            $modifiedFiles,
            $newFiles
        );

        return $this->branchModificationsCached[$cacheKey];
    }
}
