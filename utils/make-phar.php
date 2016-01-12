<?php

require './vendor/autoload.php';

use Symfony\Component\Finder\Finder;

if (!isset($argv[1])) {
  echo "usage: php -dphar.readonly=0 $argv[0] <path> [--quiet]\n";
  exit(1);
}

define('DEST_PATH', $argv[1]);

define('BE_QUIET', in_array('--quiet', $argv));

/**
 * Adds a file to the PHAR
 *
 * @param [Phar]   $phar Phar archive resource
 * @param [string] $path Path to the file to add
 * @return [void]
 */
function addFile($phar, $path) {
  $key = str_replace('./', '', $path);

  if (!BE_QUIET) {
    echo "$key - $path\n";
  }

  $phar[ $key ] = file_get_contents($path);
}

$phar = new Phar(DEST_PATH, 0, 'terminus.phar');

$phar->startBuffering();

// PHP files
$finder = new Finder();
$finder
    ->files()
    ->ignoreVCS(true)
    ->in('./assets')
    ->in('./php')
    ->in('./vendor')
    ->in('./config')
    ->exclude('test')
    ->exclude('tests')
    ->exclude('Tests')
    ->exclude('php-cli-tools/examples');

foreach ($finder as $file) {
  addFile($phar, $file);
}

// Non-PHP Files
$finder = new Finder();
$finder
    ->files()
    ->ignoreVCS(true)
    ->ignoreDotFiles(false)
    ->in('./templates');

foreach ($finder as $file) {
  addFile($phar, $file);
}

addFile($phar, './vendor/autoload.php');
addFile($phar, './vendor/rmccue/requests/library/Requests/Transport/cacert.pem');

$phar->setStub(
  <<<EOB
#!/usr/bin/env php
<?php
Phar::mapPhar();
include 'phar://terminus.phar/php/boot-phar.php';
__HALT_COMPILER();
?>
EOB
);

$phar->stopBuffering();

echo "Generated " . DEST_PATH . "\n";
