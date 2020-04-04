<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\CodeIssue;
use PlotBox\PhpGitOps\RelativeFile;

class LineFilter
{
    /** @var string[] */
    private $modifiedFiles;
    /** @var Git */
    private $gitService;
    /** @var BranchModifications */
    private $branchModifications;

    public function __construct(Git $gitService, BranchModifications $branchModifications)
    {
        $this->gitService = $gitService;
        $this->branchModifications = $branchModifications;
        $this->modifiedFiles = array_flip($branchModifications->getModifiedFilePaths());
    }

    /**
     * @param CodeIssue[] $issues
     * @return CodeIssue[]
     */
    public function filterIssues(array $issues)
    {
        $resultIssues = [];
        foreach ($issues as $issue) {
            $file = new RelativeFile($issue->getFile());
            $line = (int) $issue->getLine();
            if ($this->lineWasTouched($file, $line)) {
                $resultIssues[] = $issue;
            }
        }

        return $resultIssues;
    }

    /**
     * Check an exact line of code to see if was touched
     *
     * @param RelativeFile $file
     * @param int $line
     * @return bool
     */
    private function lineWasTouched(RelativeFile $file, $line)
    {
        $filePath = $file->getPath();
        if (!key_exists($filePath, $this->modifiedFiles)) {
            return false;
        }

        if ($this->branchModifications->isNewFile($file)) {
            return true;
        }

        if ($this->branchModifications->getUnstagedChanges()->wasModified($file, $line)) {
            return true;
        }
        $lineCommit = null;
        try {
            $lineCommit = $this->gitService->getLastCommitForLineOfCode(
                $filePath,
                $line
            );
        } catch (LineUnstagedException $e) {
            // Uncommitted line of code is always touched
            return true;
        } catch (LineNotExistException $e) {
            // Possible de-sync between git and phpcs, just return true
            return true;
        }

        if ($this->branchModifications->commitPartOfModifications($lineCommit)) {
            return true;
        }

        return false;
    }

    /**
     * @return BranchModifications
     */
    public function getBranchModifications()
    {
        return $this->branchModifications;
    }
}