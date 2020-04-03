<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\RelativeFile;

class FileFilter
{
    /** @var string */
    private $projectDirectory;

    /**
     * @param string $projectDirectory
     */
    public function __construct($projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @param RelativeFile[] $fileList
     * @return RelativeFile[]
     */
    public function getFilteredFiles(array $fileList)
    {
        foreach ($fileList as $key => $file) {
            $filePath = $file->getPath();
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($extension !== 'php') {
                unset($fileList[$key]);
                continue;
            }

            if (!file_exists($this->projectDirectory . '/' . $filePath)) {
                unset($fileList[$key]);
            }
        }

        return $fileList;
    }
}
