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

  /**
   * @expectedException \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage There is no configuration option set with the key {key}.
   */
  public function testGetConfig() {
    $format = $this->runner->getConfig('format');
    $this->assertTrue(
      in_array($format, ['normal', 'json', 'silent', 'bash'])
    );

    $config = $this->runner->getConfig();
    $this->assertInternalType('array', $config);

    $invalid = $this->runner->getConfig('invalid');
  }

  public function testGetLogger() {
    $logger = $this->runner->getLogger();
    $this->assertTrue(strpos(get_class($logger), 'Logger') !== false);
  }

  public function testGetOutputter() {
    $outputter = $this->runner->getOutputter();
    $this->assertTrue(strpos(get_class($outputter), 'Outputter') !== false);
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

}
