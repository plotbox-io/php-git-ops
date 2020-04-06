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

        // New files appear to be being picked up here also now. May be possible to
        // drop the separate newly added files method below..
        $modifiedFiles = $this->git->parseTouchedLines(
            new ComparisonDiffCommand(
                $current,
                $ancestorBranch
            )
        );
        $newFiles = $this->git->getNewlyAddedFiles($mergeBase, $current);
        $unstagedChanges = $this->git->parseTouchedLines(new UnstagedDiffCommand());
        $this->branchModificationsCached[$cacheKey] = new BranchModifications(
            $modifiedFiles,
            $newFiles,
            $unstagedChanges
        );

        return $this->branchModificationsCached[$cacheKey];
    }
}
