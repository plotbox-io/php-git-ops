<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

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
     * @param RelativeFile|null $singleTargetFile
     * @return BranchModifications
     */
    public function getBranchModifications(RelativeFile $singleTargetFile = null)
    {
        $branchComparer = new BranchComparer($this->git);
        $currentBranch = $this->git->getCurrentBranch();
        $ancestorBranch = $branchComparer->getAncestorBranch($currentBranch);
        return $this->getBranchModificationsSpecified(
            $this->git->getMergeBase($ancestorBranch, $currentBranch),
            $this->git->getLatestCommit($currentBranch),
            $singleTargetFile
        );
    }

    /**
     * @param Pointer $ancestorBranch
     * @param Pointer $current
     * @param RelativeFile|null $singleTargetFile
     * @return BranchModifications
     */
    public function getBranchModificationsSpecified(Pointer $ancestorBranch, Pointer $current, RelativeFile $singleTargetFile = null)
    {
        $mergeBase = $this->git->getMergeBase($ancestorBranch, $current);
        $cacheKey = $mergeBase->getName() . '~' . $current->getName();
        if (isset($this->branchModificationsCached[$cacheKey])) {
            return $this->branchModificationsCached[$cacheKey];
        }

        $modifiedFiles = $singleTargetFile ? [$singleTargetFile] : $this->git->parseTouchedLines(
            new ComparisonDiffCommand(
                $current,
                $ancestorBranch
            )
        );
        $newFiles = $this->git->getNewlyAddedFiles($mergeBase, $current);
        $commitList = $this->git->getCommitsBetween($mergeBase, $current);
        $unstagedChanges = $this->git->parseTouchedLines(new UnstagedDiffCommand());

        $this->branchModificationsCached[$cacheKey] = new BranchModifications(
            $modifiedFiles,
            $newFiles,
            $commitList,
            $unstagedChanges
        );

        return $this->branchModificationsCached[$cacheKey];
    }
}
