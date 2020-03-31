<?php

namespace App\Git;

interface Pointer
{
    /**
     * @return string
     */
    public function getName();
}
