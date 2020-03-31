<?php

namespace App\Git;

use RuntimeException;

class Commit implements Pointer
{
    /** @var string */
    private $hash;

    /**
     * @param string $hash
     */
    public function __construct($hash)
    {
        if (!$hash) {
            throw new RuntimeException('Hash must not be empty');
        }

        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param Commit $other
     * @return bool
     */
    public function equals(Commit $other)
    {
        return $this->getHash() === $other->getHash();
    }

    /**
     * {@inheritDoc}
     * Alias for getHash()
     * @see Commit::getHash()
     */
    public function getName()
    {
        return $this->hash;
    }
}
