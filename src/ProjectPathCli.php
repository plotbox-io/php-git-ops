<?php

namespace App;

class ProjectPathCli
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
     * @param string $command
     * @param string|null $stdIn
     * @return string
     */
    public function getResultString($command, $stdIn = null)
    {
        $tempFilePath = null;
        if ($stdIn) {
            $tempFilePath = $this->makeTempFile($stdIn);
            $command = "cat $tempFilePath | $command";
        }

        $prevDir = getcwd();
        chdir($this->projectDirectory);
        $command = $this->silenceStdErr($command);
        $result = shell_exec($command) ?: '';
        $result = trim($result);
        chdir($prevDir);

        if ($stdIn) {
            $this->removeTempFile($tempFilePath);
        }


        return $result;
    }

    /**
     * @param string $command
     * @param string|null $stdIn
     * @return string[]
     */
    public function getResultArray($command, $stdIn = null)
    {
        $tempFilePath = null;
        if ($stdIn) {
            $tempFilePath = $this->makeTempFile($stdIn);
            $command = "cat $tempFilePath | $command";
        }

        $prevDir = getcwd();
        $command = $this->silenceStdErr($command);
        chdir($this->projectDirectory);
        exec($command, $result);
        chdir($prevDir);

        if ($stdIn) {
            $this->removeTempFile($tempFilePath);
        }

        return $result;
    }

    /**
     * @param string $content
     * @return string
     */
    private function makeTempFile($content)
    {
        $tempDirectory = sys_get_temp_dir();
        $path = tempnam($tempDirectory, "code");
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @param string $filename
     */
    private function removeTempFile($filename)
    {
        unlink($filename);
    }

    /**
     * @param string $command
     * @return string
     */
    private function silenceStdErr($command)
    {
        return $command . ' 2> /dev/null';
    }
}