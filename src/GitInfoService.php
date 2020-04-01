<?php

namespace PlotBox\PhpGitOps;

use PlotBox\PhpGitOps\Git\BranchComparer;
use PlotBox\PhpGitOps\Git\BranchModificationsFactory;
use PlotBox\PhpGitOps\Git\Git;

class GitInfoService
{
    /**
     * Convenience wrapper to find all filepaths that have been touched in
     * currently active feature branch
     *
     * @param $repoDirectory
     * @return RelativeFile[]
     */
    public function filesTouched($repoDirectory)
    {
        // Get all files 'touched' in current branch
        $git = new Git($repoDirectory);
        $branchModificationFactory = new BranchModificationsFactory($git);
        $branchComparer = new BranchComparer($git);
        $currentBranch = $git->getCurrentBranch();
        $ancestorBranch = $branchComparer->getAncestorBranch($currentBranch);
        $branchModifications = $branchModificationFactory->getBranchModifications(
            $git->getMergeBase($ancestorBranch, $currentBranch),
            $git->getLatestCommit($currentBranch)
        );
        return $branchModifications->getModifiedFilePaths();
    }
}
