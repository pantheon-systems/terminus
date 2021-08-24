<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Pantheon\Terminus\Exceptions\TerminusException;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Loader\FilesystemLoader;

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

        return copy($path, $target_dir . '/' . basename($copyfrom));
    }

    /**
     * Load the template.
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

        return file_get_contents($path);
    }


    /**
     * Returns the processed YML template.
     *
     * @param string $filename
     *   Relative path to template
     * @param array $replacements
     *   Associative array of replacements => values
     *
     * @return string
     *
     * @throws TerminusException
     */
    public static function process($filename, array $replacements = [])
    {
        static $loader, $twig;

        try {
            if (!isset($loader, $twig)) {
                $loader = new FilesystemLoader(self::getTemplatesDir());
                $twig = new Environment($loader);
            }

            return $twig->render($filename, $replacements);
        } catch (Error $e) {
            throw new TerminusException(
                'Failed rendering template {template}: {message}',
                [
                    'template' => $filename,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Return the absolute path to the template by template file name.
     *
     * @return string
     *   The template file name.
     */
    private static function path($filename)
    {
        return self::getTemplatesDir() . '/' . $filename;
    }

    /**
     * Returns the absolute path to the template directory.
     *
     * @return string
     *   Templates directory.
     */
    private static function getTemplatesDir()
    {
        return dirname(__DIR__, 3) . '/templates/aliases';
    }
}
