<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Consolidation\Comments\Comments;
use Symfony\Component\Yaml\Yaml;

class Template
{
    /**
     * Copy a file from one place to another
     *
     * @param string $copyfrom
     *   Relative path to a template file
     * @param string $target_dir
     *   Absolute path of directory to write target file into.
     */
    public static function copy($copyfrom, $target_dir)
    {
        $path = static::path($copyfrom);
        $copied = copy($path, $target_dir . '/' . basename($copyfrom));
        return $copied;
    }

    /**
     * Load the template
     *
     * @param string $filename
     *   Relative path to template to load
     *
     * @return string
     *   Template contents
     */
    public static function load($filename)
    {
        $path = static::path($filename);
        $contents = file_get_contents($path);

        return $contents;
    }

    /**
     * Return the path to the template
     *
     * @return string
     */
    public static function path($filename)
    {
        return dirname(dirname(dirname(__DIR__))) . "/templates/aliases/$filename";
    }

    /**
     * Template::process loads a template, makes all of the provided
     * replacements, and then removes the unwanted parts that are left
     * over per the rules below.
     *
     * @param string $filename
     *   Relative path to template
     * @param array $replacements
     *   Associative array of replacements => values
     */
    public static function process($filename, $replacements)
    {
        $contents = static::replace($filename, $replacements);
        $contents = static::removeUnwantedParts($contents);

        return $contents;
    }

    /**
     * Load and makes replacements in the template
     *
     * @param string $filename
     *   Relative path to template
     * @param array $replacements
     *   Associative array of replacements => values
     *
     * @return string
     */
    public static function replace($filename, $replacements)
    {
        $contents = static::load($filename);
        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        return $contents;
    }

    /**
     * Tamplate::removeUnwantedParts removes leftover markers from the template.
     *
     * Rule: If there are any unreplaced variables (e.g. '{{foo}}')
     * on a line that begins with '##', then remove all lines that
     * begin with '##'.  Otherwise, replace '##' at the beginning of
     * lines with '  '.
     *
     * @param string $contents
     *   Data to process.
     *
     * @return string
     *   Processed data.
     */
    protected static function removeUnwantedParts($contents)
    {
        if (preg_match('%^##[^\r\n]+{{%m', $contents)) {
            return preg_replace('%^##.*[\r\n]+%m', '', $contents);
        }

        return preg_replace('%^##%m', '  ', $contents);
    }
}
