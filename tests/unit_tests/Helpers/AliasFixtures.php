<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Symfony\Component\Filesystem\Filesystem;

class AliasFixtures
{
    protected static $tmpDirs = [];

    /**
     * Return the path to the fixture
     *
     * @return string
     */
    public static function path($filename)
    {
        return dirname(dirname(__DIR__)) . "/fixtures/aliases/$filename";
    }

    /**
     * Load a fixture file
     *
     * @param string $filename
     *   Relative path to fixture file
     *
     * @return string
     *   Fixture contents.
     */
    public static function load($filename)
    {
        $path = static::path($filename);
        $contents = file_get_contents($path);

        return $contents;
    }

    /**
     * Clean up any temporary directories that may have been created.
     */
    public function cleanup()
    {
        $fs = new Filesystem();
        foreach (static::$tmpDirs as $tmpDir) {
            $fs->remove($tmpDir);
        }
        static::$tmpDirs = [];
    }

    /**
     * Create a new temporary directory.
     *
     * @param string|bool $basedir Where to store the temporary directory
     * @param string|bool $name Pattern to name the temporary directory
     * @return string
     */
    public static function mktmpdir($basedir = false, $name = false)
    {
        $tmp_parent = realpath($basedir ?: sys_get_temp_dir());
        $tempfile = tempnam($tmp_parent, $name ?: 'terminus-alias-tests');
        unlink($tempfile);
        mkdir($tempfile);
        static::$tmpDirs[] = $tempfile;
        return $tempfile;
    }

    /**
     * Alias fixture representing a few sites.
     *
     * @return array
     */
    public static function aliasReplacementsFixture()
    {
        return [
            'personalsite' => static::aliasReplacement('personalsite', '33333333-3333-3333-3333-333333333333'),
            'demo' => static::aliasReplacement('demo', '44444444-4444-4444-4444-444444444444'),
            'agency' => static::aliasReplacement('agency', '55555555-5555-5555-5555-555555555555'),
        ];
    }

    /**
     * A few more site alias fixtures.
     *
     * @return array
     */
    public static function additionalAliasReplacementsFixtures()
    {
        return [
            'site201' => static::aliasReplacement('site201', 'bfc9e1d2-25f8-1379-198b-06bc018a1a86'),
            'site13' => static::aliasReplacement('site13', 'f0971262-91c2-1375-6e53-b337ae5a6d9e'),
            'site9' => static::aliasReplacement('site9', '87680dcd-20fc-7efe-71dc-91db39c42b85'),
            'site78' => static::aliasReplacement('site78', '2ad1b926-1a78-aa81-1f72-51fd6c0c2271'),
        ];
    }

    /**
     * Create an alias replacements record for a single site
     */
    protected static function aliasReplacement($name, $id)
    {
        return [
            '{{site_name}}' => $name,
            '{{env_name}}' => '*',
            '{{env_label}}' => '${env-name}',
            '{{site_id}}' => $id,
        ];
    }
}
