<?php

namespace App;

class CodeIssue
{
    /** @var RelativeFile  */
    private $file;
    /** @var int */
    private $line;

    /**
     * @param RelativeFile $file
     * @param int $line
     */
    public function __construct(RelativeFile $file, $line) {
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file->getPath();
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }
}
