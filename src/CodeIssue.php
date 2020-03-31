<?php

namespace App;

class CodeIssue
{
    /** @var RelativeFile */
    private $file;
    /** @var int */
    private $line;
    /** @var string */
    private $uniqueReference;

    /**
     * @param RelativeFile $file
     * @param int $line
     */
    public function __construct(RelativeFile $file, $line)
    {
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * Create new instance from primitives
     *
     * @param string $file
     * @param int $line
     * @param null $uniqueRef
     * @return static
     */
    public static function make($file, $line, $uniqueRef = null)
    {
        $instance = new static(
            new RelativeFile($file),
            $line
        );
        if ($uniqueRef) {
            $instance->setUniqueReference($uniqueRef);
        }

        return $instance;
    }

    /**
     * @return string
     */
    public function getUniqueReference()
    {
        return $this->uniqueReference;
    }

    /**
     * @param string $uniqueReference
     */
    public function setUniqueReference($uniqueReference)
    {
        $this->uniqueReference = $uniqueReference;
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
