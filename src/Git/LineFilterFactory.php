<?php

namespace App\Git;

class LineFilterFactory
{
    /** @var Git */
    private $gitService;
    /** @var BranchModificationsFactory */
    private $branchModificationsFactory;

    public function __construct(Git $gitService, BranchModificationsFactory $branchModificationsFactory)
    {
        $this->gitService = $gitService;
        $this->branchModificationsFactory = $branchModificationsFactory;
    }

    public function makeLineFilter(Branch $ancestorBranch)
    {
        $currentBranch = $this->gitService->getCurrentBranch();
        $branchModifications = $this->branchModificationsFactory->getBranchModifications(
            $this->gitService->getMergeBase($ancestorBranch, $currentBranch),
            $this->gitService->getLatestCommit($currentBranch)
        );

        return new LineFilter($this->gitService, $branchModifications);
    }
}