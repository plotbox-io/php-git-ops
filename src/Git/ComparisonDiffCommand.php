<?php

namespace PlotBox\PhpGitOps\Git;

class ComparisonDiffCommand implements DiffCommand
{
    /** @var string */
    private $diffFrom;
    /** @var string */
    private $diffTo;

    public function __construct(Pointer $diffFrom, Pointer $diffTo)
    {
        $this->diffFrom = $diffFrom;
        $this->diffTo = $diffTo;
    }

    /**
     * @inheritDoc
     * @see https://confluence.atlassian.com/bitbucketserverkb/understanding-diff-view-in-bitbucket-server-859450562.html
     */
    public function toString()
    {
        return "git diff --unified=0 {$this->diffTo->getName()}...{$this->diffFrom->getName()}";
    }
}
