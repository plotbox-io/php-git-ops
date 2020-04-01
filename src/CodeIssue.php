<?php

namespace PlotBox\PhpGitOps;

class CodeIssue
{
    /** @var RelativeFile */
    private $file;
    /** @var int */
    private $line;
    /** @var mixed */
    private $attachment;

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
     * @param mixed|null $attachment
     * @return static
     */
    public static function make($file, $line, $attachment = null)
    {
        $instance = new static(
            new RelativeFile($file),
            $line
        );
        if ($attachment) {
            $instance->setAttachment($attachment);
        }

        return $instance;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
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
