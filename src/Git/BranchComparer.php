<?php

namespace App\Git;

use App\Util\StringUtil;

class BranchComparer
{
    /** @internal */
    const BRANCH_MASTER = 'master';
    /** @internal */
    const BRANCH_DEVELOP = 'develop';
    /** @internal */
    const CORE_BRANCH_NUM_BACK_FOR_DIFF = 10;
    /** @internal */
    const FIXED_STANDARD_ANCESTORS = [
        self::BRANCH_MASTER,
        self::BRANCH_DEVELOP,
    ];
    /** @internal */
    const GLOB_STANDARD_ANCESTORS = [
        'sprint/*',
        'release/*',
    ];

    /** @var Git */
    private $git;

    public function __construct(Git $git)
    {
        $this->git = $git;
    }

    /**
     * @param Branch $currentBranch
     * @return Branch
     */
    public function getAncestorBranch(Branch $currentBranch)
    {
        $currentCommit = $this->git->getCommit($currentBranch);
        $developCommit = $this->git->getCommit(new Branch(self::BRANCH_DEVELOP));
        $masterCommit = $this->git->getCommit(new Branch(self::BRANCH_MASTER));

        if (StringUtil::startsWith($currentBranch->getName(), 'sprint')) {
            return new Branch(self::BRANCH_DEVELOP);
        }

        if ($currentBranch->getName() === 'master' || $currentCommit->equals($masterCommit)) {
            return new Branch(self::BRANCH_MASTER . '~' . self::CORE_BRANCH_NUM_BACK_FOR_DIFF);
        }

        if ($currentBranch->getName() === 'develop' || $currentCommit->equals($developCommit)) {
            return new Branch(self::BRANCH_DEVELOP . '~' . self::CORE_BRANCH_NUM_BACK_FOR_DIFF);
        }

        return $this->getNearestStandardAncestor($currentBranch);
    }

    /**
     * @param Branch $currentBranch
     * @return Branch
     */
    private function getNearestStandardAncestor(Branch $currentBranch)
    {
        $branchDistances = [];
        foreach ($this->getStandardAncestors() as $ancestorBranch) {
            // Note: If branch doesn't exist locally, we may be checking against an older ancestor
            // (develop/master), but that shouldn't be too bad..
            if ($this->git->branchExists($ancestorBranch)) {
                $ancestorBaseCommit = $this->git->getMergeBase($currentBranch, $ancestorBranch);
                $distance = $this->distance($currentBranch, $ancestorBaseCommit);
                if ($distance === null) {
                    $distance = PHP_INT_MAX;
                }
                $branchDistances[$ancestorBranch->getName()] = $distance;
            }
        }

        // Sort by distance to common ancestor. If there is a tie, the order provided in the
        // STANDARD_ANCESTORS constant will prevail (i.e., master, develop, sub-branches...)
        asort($branchDistances);
        $topBranchName = array_key_first($branchDistances);
        return new Branch($topBranchName);
    }

    /**
     * Get distance in number of commits between a branch and ancestor. Returns null if the commits
     * are not ancestrally connected
     *
     * @param Branch $currentBranch
     * @param Commit $ancestorCommit
     * @return int|null
     */
    private function distance(Branch $currentBranch, Commit $ancestorCommit)
    {
        // If the ancestor base is same as current commit, then distance = 0
        $currentCommit = $this->git->getCommit($currentBranch);
        if ($currentCommit->equals($ancestorCommit)) {
            return 0;
        }

        $commits = $this->git->getCommitsBetween(
            $ancestorCommit,
            $this->git->getCommit($currentBranch)
        );
        return count($commits) ?: null;
    }

    /**
     * @return Branch[]
     */
    private function getStandardAncestors()
    {
        $ancestors = [];

        foreach (self::FIXED_STANDARD_ANCESTORS as $fixedAncestor) {
            $ancestors[] = new Branch($fixedAncestor);
        }
        foreach (self::GLOB_STANDARD_ANCESTORS as $ancestorGlob) {
            array_push(
                $ancestors,
                ...$this->git->findBranches($ancestorGlob)
            );
        }

        return $ancestors;
    }
}