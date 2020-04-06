<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

interface FileFilter
{
    /**
     * @param RelativeFile[] $fileList
     * @return RelativeFile[]
     */
    public function getFilteredFiles(array $fileList);
}
