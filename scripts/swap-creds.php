#!/usr/bin/env php
<?php
/**
 * This script exists to anonymize the account information involved in the recording of API testing
 * fixtures on production. It will exchange the credentials in the testing fixture for those in the
 * file describing the credentials to be used when running the testing suite in CI.
 */

if (file_exists($path = __DIR__ . '/vendor/autoload.php')
    || file_exists($path = __DIR__ . '/../vendor/autoload.php')
    || file_exists($path = __DIR__ . '/../../autoload.php')
    || file_exists($path = __DIR__ . '/../../../autoload.php')
) {
    include_once($path);
} else {
    throw new \Exception('Could not locate autoload.php');
}

use Symfony\Component\Yaml\Yaml;
use Pantheon\Terminus\FeatureTests\FeatureContext;

const SUITE = 'default';

$args = $_SERVER['argv'];
if (($_SERVER['argc'] !== 4) || in_array('--help', $args)) {
    die('Usage: ./swap-creds.php <fixture file name> <old creds config file name> <new creds config file name>' . PHP_EOL);
}

list($script, $fixture_file, $old_creds_file, $new_creds_file) = $args;

die(str_replace(
    getConfig($old_creds_file),
    getConfig($new_creds_file),
    getFile($fixture_file)
));

/**
 * Retrieves the config file, parses it, and sorts it for replacement use
 *
 * @param string $file_name Name of the configuration file
 * @return array Context configuration parameters found in the given file
 */
function getConfig($file_name)
{
    return sortList(getConfigData(getFile($file_name)));
}

/**
 * Retrieves the context configuration parameters from the Behat config file
 *
 * @param string $file_contents Contents of the config file read
 * @return array Parameters read from the file contents
 */
function getConfigData($file_contents)
{
    $data = Yaml::parse(
        str_replace(
            '[ ',
            '[ "',
            str_replace(' ]', '" ]', $file_contents)
        )
      );
    return $data['default']['suites'][SUITE]['contexts'][0][FeatureContext::class]['parameters'];
}

/**
 * Gets the contents of a file
 * NOTE this is just here in case I want to later replace it with Symfony
 *
 * @param string $file_name Name of the file to read
 * @return string Contents of the file read
 */
function getFile($file_name)
{
  return file_get_contents($file_name);
}

/**
 * Sorts an array by its values' lengths, long to short
 *
 * @param array $list The list to sort.
 * @return array That list, sorted.
 */
function sortList(array $list)
{
    usort($list, function($a, $b) {
        return strlen($b) <=> strlen($a);
    });
    return $list;
}
