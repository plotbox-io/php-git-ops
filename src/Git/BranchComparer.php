<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\Util\StringUtil;

class BranchComparer
{
    /** @internal */
    const BRANCH_MASTER = 'origin/master';
    /** @internal */
    const BRANCH_DEVELOP = 'origin/develop';
    /** @internal */
    const CORE_BRANCH_NUM_BACK_FOR_DIFF = 10;
    /** @var string[] */
    private static $globStandardAncestors = [
        'origin/release/*'
    ];
    /** @var string[] */
    private static $fixedStandardAncestors = [
        self::BRANCH_MASTER,
        self::BRANCH_DEVELOP,
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

        if (StringUtil::startsWith($currentBranch->getName(), 'release')) {
            return new Branch(self::BRANCH_MASTER);
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
            // Note: If branch doesn't exist locally (i.e., sprint or release), we may be checking against
            // an older ancestor (develop/master), but that shouldn't be too bad..
            if (!$this->git->branchExists($ancestorBranch)) {
                continue;
            }

            // No point comparing to self (e.g., if is release branch)
            if($this->endsWith($ancestorBranch->getName(), $currentBranch->getName())) {
                continue;
            }

            $ancestorBaseCommit = $this->git->getMergeBase($currentBranch, $ancestorBranch);
            $distance = $this->distance($currentBranch, $ancestorBaseCommit) ?? PHP_INT_MAX;
            $distance2 = $this->distance($ancestorBranch, $ancestorBaseCommit) ?? PHP_INT_MAX;
            $branchDistances[$ancestorBranch->getName()] = $distance + $distance2;
        }
        return $this->getClosestBranch($branchDistances);
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

        foreach (self::$fixedStandardAncestors as $fixedAncestor) {
            $ancestors[] = new Branch($fixedAncestor);
        }
        foreach (self::$globStandardAncestors as $ancestorGlob) {
            foreach ($this->git->findBranches($ancestorGlob) as $foundBranch) {
                $ancestors[] = $foundBranch;
            }
        }

        return $ancestors;
    }

    /**
     * Sort by distance to common ancestor. If there is a tie, STANDARD_ANCESTORS will
     * be preferred (i.e., master, develop)
     *
     * @param int[] $branchDistances
     * @return Branch
     */
    private function getClosestBranch(array $branchDistances)
    {
        asort($branchDistances);

        $topDistances = [];
        $bestDistance = reset($branchDistances);
        foreach ($branchDistances as $name => $distance) {
            if ($distance === $bestDistance) {
                $topDistances[$name] = $distance;
            } else {
                break;
            }
        }

        // There was a tie. Prefer fixed ancestor if available
        if (count($topDistances) > 1) {
            foreach ($topDistances as $name => $distance) {
                if (in_array($name, self::$fixedStandardAncestors)) {
                    return new Branch($name);
                }
            }
        }

        reset($branchDistances);
        $topBranchName = key($branchDistances);
        return new Branch($topBranchName);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}
