<?php

namespace App\Git;

class LineFilterFactory
{
    /** @var Git */
    private $gitService;
    /** @var BranchModificationsFactory */
    private $branchModificationsFactory;

    public function __construct(Git $gitService)
    {
        $this->gitService = $gitService;
        $this->branchModificationsFactory = new BranchModificationsFactory($this->gitService);
    }

    /**
     * @return LineFilter
     */
    public function makeLineFilter()
    {
        $branchComparer = new BranchComparer($this->gitService);
        $currentBranch = $this->gitService->getCurrentBranch();
        $ancestorBranch = $branchComparer->getAncestorBranch($currentBranch);

        $branchModifications = $this->branchModificationsFactory->getBranchModifications(
            $this->gitService->getMergeBase($ancestorBranch, $currentBranch),
            $this->gitService->getLatestCommit($currentBranch)
        );

        return new LineFilter($this->gitService, $branchModifications);
    }
}