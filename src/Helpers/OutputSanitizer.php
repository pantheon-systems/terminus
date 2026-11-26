<?php

namespace Pantheon\Terminus\Helpers;

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Class OutputSanitizer
 * @package Pantheon\Terminus\Helpers
 */
class OutputSanitizer
{
    /**
     * Makes a value safe to print: redacts URL credentials, strips control
     * characters, and escapes Symfony formatter tags.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function sanitize($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $value = preg_replace('#([a-z][a-z0-9+.\-]*://)[^/?\#\s@]*@#i', '$1***@', $value);
        $value = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]|\xC2[\x80-\x9F]/', '', $value);
        return OutputFormatter::escape($value);
    }
}
