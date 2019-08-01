<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\AliasData;
use Pantheon\Terminus\Helpers\AliasEmitters\AliasCollection;
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
     * Alias fixture representing a few sites, some with simulated multidev
     * environments.
     *
     * @return array
     */
    public static function standardAliasFixture()
    {
        return [
            'personalsite' => [],
            'demo' => [
                'multidev'   => ['33333333-3333-3333-3333-333333333333', '33333333333333333333333333333333', '33333'],
            ],
            'agency' => [
                'feature24'  => ['44444444-4444-4444-4444-444444444444', '44444444444444444444444444444444', '44444'],
                'feature8'   => ['33333333-3333-3333-3333-333333333333', '33333333333333333333333333333333', '33333'],
                'feature101' => ['55555555-5555-5555-5555-555555555555', '55555555555555555555555555555555', '55555'],
            ],
        ];
    }

    /**
     * A few more site alias fixtures.
     *
     * @return array
     */
    public static function additionalAliasFixtures()
    {
        return [
            'site201' => [
                'env5' => ['bfc9e1d2-25f8-1379-198b-06bc018a1a86', '3e85db4276f729f4ee48d20cd236c356', '96083'],
                'env12' => ['f0971262-91c2-1375-6e53-b337ae5a6d9e', '9882a33cafee03c4f56b4acad7f02e17', '54223'],
                'env8' => ['87680dcd-20fc-7efe-71dc-91db39c42b85', 'c8dee46096f5a6ab4bf521cd1daafed7', '90680'],
            ],
            'site13' => [
                'red' => ['3f727447-ff93-f350-779e-a6bcca2f0bef', 'fd5c64da23dd80c74db3d6ac8ddb441a', '94692'],
                'blue' => ['fd06a457-4154-edaa-a33a-5399f52a019e', '311eccab54a1bd653cea97af9448f1b8', '34746'],
                'green' => ['2ad1b926-1a78-aa81-1f72-51fd6c0c2271', '8d469bcf1f35f471e5a11d540b746fea', '63310'],
            ],
            'site9' => [],
            'site78' => [],
        ];
    }

    /**
     * Returns an alias collection fixture
     *
     * @param array $aliasData
     *   Basic data to inject into each alias fixture.
     * @param bool $includeDbUrl
     *   Inject the database information as well.
     *
     * @return AliasCollection
     */
    public static function aliasCollection($aliasData = [], $includeDbUrl = true)
    {
        if (empty($aliasData)) {
            $aliasData = static::standardAliasFixture();
        }
        $collection = new AliasCollection();
        foreach ($aliasData as $site_name => $envs) {
            $envs = $envs + [
                'live' => ['00000000-0000-0000-0000-000000000000', '00000000000000000000000000000000', '00000'],
                'test' => ['11111111-1111-1111-1111-111111111111', '11111111111111111111111111111111', '11111'],
                'dev'  => ['22222222-2222-2222-2222-222222222222', '22222222222222222222222222222222', '22222'],
            ];
            foreach ($envs as $env_name => $env_data) {
                $site_id = array_shift($env_data);
                $db_password = array_shift($env_data);
                $db_port = array_shift($env_data);
                $alias = new AliasData(
                    $site_name,
                    $env_name,
                    $site_id,
                    $includeDbUrl ? $db_password : '',
                    $includeDbUrl ? $db_port : ''
                );
                $collection->add($alias);
            }
        }
        return $collection;
    }
}
