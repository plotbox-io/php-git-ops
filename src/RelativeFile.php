<?php

namespace PlotBox\PhpGitOps;

use RuntimeException;

class RelativeFile
{
    /** @var string */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $path = trim($path);
        if (substr($path, 0, 1) === '/') {
            throw new RuntimeException("Path must be relative");
        }

        $this->path = $path;
    }

    /**
     * @param string $projectDirectory
     * @param string $absolutePath
     * @return self
     */
    public static function fromAbsolute($projectDirectory, $absolutePath)
    {
        $relativePath = preg_replace("|^{$projectDirectory}/?|", '', $absolutePath);

        return new self($relativePath);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
