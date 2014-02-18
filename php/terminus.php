<?php

define( 'TERMINUS', true );
define( 'TERMINUS_VERSION', '0.0-dev' );

include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/class-terminus.php';

\Terminus\Utils\load_dependencies();

$cache = Terminus::get_cache();

$strict = in_array('--strict', $_SERVER['argv']);
$arguments = new \cli\Arguments(compact('strict'));

$arguments->addFlag(array('verbose', 'v'), 'Turn on verbose output');
$arguments->addFlag('version', 'Display the version');
$arguments->addFlag(array('quiet', 'q'), 'Disable all output');
$arguments->addFlag(array('help', 'h'), 'Show this help screen');

$arguments->addOption(array('cache', 'C'), array(
  'default'     => $cache->get_root(),
  'description' => 'Set the cache directory'));

$arguments->parse();
if ($arguments['help'] || count($arguments->getArguments()) == 0) {
  echo "Pantheon Terminus\n";
  echo $arguments->getHelpScreen();
  echo "\n\n";
}
