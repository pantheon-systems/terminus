<?php

use Terminus\Runner;

class RunnerTest extends PHPUnit_Framework_TestCase {

  /**
   * @var Runner
   */
  private $runner;

  public function __construct() {
    $this->runner = new Runner();
  }

  public function testGetRootCommand() {
    $root_command = $this->runner->getRootCommand();
    $this->assertTrue(
      strpos(get_class($root_command), 'RootCommand') !== false
    );

    // Make sure the core commands have loaded
    $commands = array('art', 'auth', 'cli', 'drush', 'help', 'machine-tokens',
      'organizations', 'site', 'sites', 'upstreams', 'workflows', 'wp');
    foreach ($commands as $command) {
      $args = array($command);
      $this->assertTrue($root_command->findSubcommand($args) !== false);
    }

    // Make sure the correct number of parameters are configured.
    $desc = $root_command->getLongdesc();
    $this->assertTrue(count($desc['parameters']) == 4);

  }

  public function testRunCommand() {
    //$runner = new Runner();
    //$this->assertInstanceOf('Terminus\Runner', $runner);
    //$args       = ['site'];
    //$assoc_args = ['site' => 'phpunittest'];
    //$return = $runner->runCommand($args, $assoc_args);
    //$this->assertNull($return);
  }

}
