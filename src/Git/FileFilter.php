<?php

namespace App\Git;

use App\RelativeFile;
use App\Util\StringUtil;

class FileFilter
{
    /** @internal */
    const EXCLUDE_DIRS = [
        'vue2',
        'tools',
        'tests',
        'cache',
        'config',
        '.docker'
    ];
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

            foreach (self::EXCLUDE_DIRS as $exludedDir) {
                if (StringUtil::startsWith($filePath, $exludedDir)) {
                    unset($fileList[$key]);
                    continue;
                }
            }

            if (!file_exists($this->projectDirectory . '/' . $filePath)) {
                unset($fileList[$key]);
            }
        }

        return $fileList;
    }
}
