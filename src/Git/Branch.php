<?php

namespace App\Git;

class Branch
{
    /** @var string */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Branch $other
     * @return bool
     */
    public function equals(Branch $other)
    {
        return $this->getName() === $other->getName();
    }
}
