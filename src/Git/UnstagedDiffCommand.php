<?php

namespace PlotBox\PhpGitOps\Git;

class UnstagedDiffCommand implements DiffCommand
{

    /**
     * @inheritDoc
     */
    public function toString()
    {
        return 'git diff --unified=0';
    }
}
