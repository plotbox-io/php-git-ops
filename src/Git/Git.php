<?php

namespace PlotBox\PhpGitOps\Git;

use PlotBox\PhpGitOps\ProjectPathCli;
use PlotBox\PhpGitOps\RelativeFile;
use PlotBox\PhpGitOps\Util\StringUtil;
use RuntimeException;

class Git
{
    /** @var string */
    private $repoDirectory;
    /** @var ProjectPathCli */
    private $cli;
    /** @var string[][] */
    private $blameCache;

    /**
     * @param string $projectPath
     */
    public function __construct($projectPath)
    {
        $this->cli = new ProjectPathCli($projectPath);
        $this->repoDirectory = $projectPath;
        $this->blameCache = [];
    }

    /**
     * @param Pointer $mergeBase
     * @param Pointer $current
     * @return RelativeFile[]
     */
    public function getNewlyAddedFiles(Pointer $mergeBase, Pointer $current)
    {
        $untrackedNonIgnoredFiles = $this->getUntrackedNonIgnoredFiles();
        $stagedNewFiles = $this->getStagedNewFiles();
        $committedNewFiles = $this->getCommittedNewFiles($mergeBase, $current);
        $uncommittedRenamedFiles = $this->getUncommittedRenamedFiles();

        $allFiles = array_merge(
            $untrackedNonIgnoredFiles,
            $uncommittedRenamedFiles,
            $stagedNewFiles,
            $committedNewFiles
        );

        // De-dup (just in-case there is overlap with any of these commands)
        $resultFiles = [];
        /** @var RelativeFile $file */
        foreach ($allFiles as $file) {
            $resultFiles[$file->getPath()] = $file;
        }

        return $resultFiles;
    }

    /**
     * @param Pointer $current
     * @param Pointer $mergeBase
     * @return Commit[]
     */
    public function getCommitsBetween(Pointer $mergeBase, Pointer $current)
    {
        $shellCommand = "git log --pretty=tformat:\"%H\" --ancestry-path {$mergeBase->getName()}..{$current->getName()}";
        $commitList = $this->cli->getResultArray($shellCommand);
        $allCommits = [];
        foreach ($commitList as $commitHash) {
            $allCommits[] = new Commit($commitHash);
        }

        return $allCommits;
    }

    /**
     * @return UnstagedChanges
     * @see https://stackoverflow.com/a/24456418
     */
    public function getUnstagedChanges()
    {
        $changes = new UnstagedChanges();

        $shellCommand = "git diff --unified=0 | grep -Po '^\+\+\+ ./\K.*|^\+\+\+ \K\/dev\/null|^@@ -[0-9]+(,[0-9]+)? \+\K[0-9]+(,[0-9]+)?(?= @@)'";
        $commandResultLines = $this->cli->getResultArray($shellCommand);

        $currentFilePath = null;
        foreach ($commandResultLines as $resultLine) {
            // /dev/null is used to signal created or deleted files
            // @see https://git-scm.com/docs/git-diff/1.7.5
            // We just ignore these as they are recorded in the changes as new files anyway
            // so diffs will be handled correctly
            if ($resultLine === '/dev/null') {
                $currentFilePath = $resultLine;
                continue;
            }

            // Ignore the non-file-path output
            if (file_exists($this->repoDirectory . '/' . $resultLine)) {
                $currentFilePath = $resultLine;
                continue;
            }

            if (!preg_match('|[0-9,]+|', $resultLine)) {
                throw new RuntimeException(
                    "Error: Expecting either valid filenames or unified diff line number syntax"
                );
            }

            // Ignore processing changed lines for /dev/null (see above comment)
            if ($currentFilePath === '/dev/null') {
                continue;
            }

            $lineData = explode(',', $resultLine);
            $startLine = (int) $lineData[0];
            $count = count($lineData) === 1 ? 1 : (int) $lineData[1];
            $lastLine = $startLine + $count;
            $file = new RelativeFile($currentFilePath);
            for ($currentLine = $startLine; $currentLine < $lastLine; $currentLine++) {
                $changes->addTouchedLine(
                    $file,
                    $currentLine
                );
            }
        }

        return $changes;
    }

    /**
     * @param Commit $mergeBase
     * @param Commit $current
     * @return RelativeFile[]
     */
    public function getModifiedFiles(Pointer $mergeBase, Pointer $current)
    {
        $shellCommand = "git diff --diff-filter=d --name-only {$mergeBase->getName()} {$current->getName()} --raw";
        $modifiedFiles = $this->cli->getResultArray($shellCommand);

        return $this->toRelativeFiles($modifiedFiles);
    }

    /**
     * @param Branch $branch
     * @return Commit
     */
    public function getLatestCommit(Branch $branch)
    {
        $hash = $this->cli->getResultString("git rev-parse {$branch->getName()}");

        return new Commit($hash);
    }

    /**
     * Finds the best common ancestor(s) between two commits. One common ancestor is better
     * than another common ancestor if the latter is an ancestor of the former
     *
     * @param Branch $ancestor
     * @param Branch $branch
     * @return Commit
     */
    public function getMergeBase(Branch $ancestor, Branch $branch)
    {
        $shellCommand = "git merge-base \"{$branch->getName()}\" \"{$ancestor->getName()}\"";
        $hash = $this->cli->getResultString($shellCommand);

        return new Commit($hash);
    }

    /**
     * @return Branch
     */
    public function getCurrentBranch()
    {
        $branchName = $this->cli->getResultString('git branch | grep \* | cut -d \' \' -f2');

        return new Branch($branchName);
    }

    /**
     * Return the last commit hash for a particular line of code
     *
     * @param string $file
     * @param int $line
     * @return Commit
     * @throws LineNotExistException
     * @throws LineUnstagedException
     */
    public function getLastCommitForLineOfCode($file, $line)
    {
        if (array_key_exists($file, $this->blameCache)) {
            $blameLines = $this->blameCache[$file];
        } else {
            // We can clear the cache each time there is a miss because we know we will
            // never get another hit (all files are in order)
            $this->blameCache = [];

            /*
             * -l >> Show long rev
             * --root >> Do not treat root commits as boundaries
             */
            $shellCommand = "git blame --root -l {$file} 2>/dev/null";
            $blameLines = $this->cli->getResultArray($shellCommand);
            $this->blameCache[$file] = $blameLines;
        }

        $lineNumberIndex = $line - 1;
        if (!key_exists($lineNumberIndex, $blameLines)) {
            throw new LineNotExistException();
        }

        $blameLine = $blameLines[$lineNumberIndex];
        preg_match('/^[\w]{40}/', $blameLine, $matches);

        // Special flag for lines that are 'not yet committed'
        if ($matches[0] === '0000000000000000000000000000000000000000') {
            throw new LineUnstagedException();
        }

        return new Commit($matches[0]);
    }

    /**
     * Get the current commit of a particular branch
     *
     * @param Branch $branch
     * @return Commit
     */
    public function getCommit(Branch $branch)
    {
        $commitHash = $this->cli->getResultString("git rev-parse {$branch->getName()}");
        return new Commit($commitHash);
    }

    /**
     * Checks if a branch exists locally. If origin/some-branch is given, the existence of
     * a local branch with the same name will be checked instead
     *
     * @param Branch $branch
     * @return bool
     */
    public function branchExists(Branch $branch)
    {
        $branchName = $branch->getName();
        if (StringUtil::startsWith($branchName, 'origin/')) {
            $branchName = StringUtil::trimFromStart('origin/', $branchName);
        }

        $shellCommand = "git show-ref --verify --quiet refs/heads/{$branchName} && echo 'YES' || echo 'NO'";
        $result = $this->cli->getResultString($shellCommand);
        return $result === 'YES';
    }

    /**
     * @param string $glob
     * @return Branch[]
     */
    public function findBranches($glob)
    {
        $escapedGlob = escapeshellarg($glob);
        $shellCommand = "git branch --list {$escapedGlob}";
        $branches = $this->cli->getResultArray($shellCommand);

        $branchesResult = [];
        foreach ($branches as $branchName) {
            $branchesResult[] = new Branch(trim($branchName));
        }

        return $branchesResult;
    }

    public function fetchAll()
    {
        $this->cli->getResultString('git fetch --all');
    }

    /**
     * @param string[] $modifiedFiles
     * @return array
     */
    private function toRelativeFiles($modifiedFiles)
    {
        $resultFiles = [];
        foreach ($modifiedFiles as $modifiedFile) {
            $resultFiles[] = new RelativeFile($modifiedFile);
        }

        return $resultFiles;
    }

    /**
     * @return RelativeFile[]
     * @see https://stackoverflow.com/a/4855096
     */
    private function getUntrackedNonIgnoredFiles()
    {
        $shellCommand = 'git ls-files . --exclude-standard --others';
        $untrackedNonIgnoredFiles = $this->cli->getResultArray($shellCommand);

        return $this->toRelativeFiles($untrackedNonIgnoredFiles);
    }

    /**
     * @return RelativeFile[]
     * @see https://stackoverflow.com/a/15535048
     */
    private function getStagedNewFiles()
    {
        $shellCommand = 'git diff --name-only --diff-filter=A HEAD';
        $untrackedNonIgnoredFiles = $this->cli->getResultArray($shellCommand);

        return $this->toRelativeFiles($untrackedNonIgnoredFiles);
    }

    /**
     * @param Pointer $mergeBase
     * @param Pointer $current
     * @return RelativeFile[]
     * @see https://stackoverflow.com/a/15535048
     */
    private function getCommittedNewFiles(Pointer $mergeBase, Pointer $current)
    {
        $shellCommand = "git diff --diff-filter=A --name-only {$mergeBase->getName()} {$current->getName()} --raw";
        $untrackedNonIgnoredFiles = $this->cli->getResultArray($shellCommand);

        return $this->toRelativeFiles($untrackedNonIgnoredFiles);
    }

    /**
     * @return RelativeFile[]
     */
    private function getUncommittedRenamedFiles()
    {
        $regex = <<<RGX
/renamed: {4}([a-z0-9\/\\\.]+) -> ([a-z0-9\/\\\.]+)/i
RGX;

        $shellCommand = "git status | grep renamed:";
        $gitResults = $this->cli->getResultArray($shellCommand);
        $results = [];
        foreach ($gitResults as $gitResult) {
            preg_match($regex, $gitResult, $matches);
            $toFilename = $matches[2];
            $results[] = new RelativeFile($toFilename);
        }

        return $results;
    }
}