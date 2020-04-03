<?php

namespace PlotBox\PhpGitOps\Util;

class StringUtil
{
    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return string
     */
    public static function trimFromStart($needle, $haystack)
    {
        return $str = substr($haystack, strlen($needle));
    }
}
