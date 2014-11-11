<?php

require './vendor/autoload.php';

use Symfony\Component\Finder\Finder;

if ( !isset( $argv[1] ) ) {
  echo "usage: php -dphar.readonly=0 $argv[0] <path> [--quiet]\n";
  exit(1);
}

define( 'DEST_PATH', $argv[1] );

define( 'BE_QUIET', in_array( '--quiet', $argv ) );

function add_file( $phar, $path ) {
  $key = str_replace( './', '', $path );

  if ( !BE_QUIET )
    echo "$key - $path\n";

  $phar[ $key ] = file_get_contents( $path );
}

$phar = new Phar( DEST_PATH, 0, 'terminus.phar' );

$phar->startBuffering();

// PHP files
$finder = new Finder();
$finder
  ->files()
  ->ignoreVCS(true)
  ->in('./php')
  ->in('./vendor')
  ->exclude('test')
  ->exclude('tests')
  ->exclude('Tests')
  ->exclude('php-cli-tools/examples')
  ;

foreach ( $finder as $file ) {
  add_file( $phar, $file );
}

// Non-PHP Files
$finder = new Finder();
$finder
  ->files()
  ->ignoreVCS(true)
  ->ignoreDotFiles(false)
  ->in('./templates')
  ;

foreach ( $finder as $file ) {
  add_file( $phar, $file );
}

add_file( $phar, './vendor/autoload.php' );
add_file( $phar, './vendor/rmccue/requests/library/Requests/Transport/cacert.pem' );

$phar->setStub( <<<EOB
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
