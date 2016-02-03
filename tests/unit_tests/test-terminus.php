<?php

use Terminus\Dispatcher;

/**
 * Testing class for Terminus
 */
class TerminusTest extends PHPUnit_Framework_TestCase {

  public function testConstruct() {
    $terminus = new Terminus();
    $this->assertTrue(get_class($terminus) == 'Terminus');
  }

  public function testGetCache() {
    setDummyCredentials();
    $cache   = Terminus::getCache();
    setDummyCredentials();
    $session = $cache->getData('session');
    $this->assertEquals($session->user_uuid, '0ffec038-4410-43d0-a404-46997f672d7a');
  }

  /**
   * @expectedException \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage Unknown config option "{key}".
   */
  public function testGetConfig() {
    $format = Terminus::getConfig('format');
    $this->assertEquals($format, 'normal');

    $config = Terminus::getConfig();
    $this->assertInternalType('array', $config);

    $invalid = Terminus::getConfig('invalid');
  }

  public function testGetLogger() {
    $logger = Terminus::getLogger();
    $this->assertTrue(strpos(get_class($logger), 'Logger') !== false);
  }

  public function testGetOutputter() {
    $outputter = Terminus::getOutputter();
    $this->assertTrue(strpos(get_class($outputter), 'Outputter') !== false);
  }

  public function testGetPhpBinary() {
    $php_binary = PHP_BINARY;

    putenv("TERMINUS_PHP_USED=$php_binary");
    $this->testLaunchSelf();

    putenv("TERMINUS_PHP_USED=");
    putenv("TERMINUS_PHP=$php_binary");
    $this->testLaunchSelf();

    putenv("TERMINUS_PHP=");
    $this->testLaunchSelf();
  }

  public function testGetRootCommand() {
    $root_command = Terminus::getRootCommand();
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

  public function testGetRunner() {
    $runner = Terminus::getRunner();
    $this->assertTrue(
      strpos(get_class($runner), 'Runner') !== false
    );
  }

  public function testIsTest() {
    $this->assertTrue(Terminus::isTest());
  }

  public function testLaunch() {
    $file_name = '/tmp/output';
    //Testing a good command
    setOutputDestination($file_name);
    $return = Terminus::launch("ls tests/ > $file_name");
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, 'unit_tests') !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);

    //Testing a bad command
    setOutputDestination($file_name);
    $return = Terminus::launch("exit 1 > $file_name", false);
    $output = retrieveOutput($file_name);
    $this->assertEquals($return, 1);
    resetOutputDestination($file_name);
  }

  public function testLaunchSelf() {
    $file_name = '/tmp/output';
    //Testing the library route
    setOutputDestination($file_name);
    $return = Terminus::launchSelf("art unicorn > $file_name");
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, "<.'_.''") !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);

    //Testing the command-line route
    setOutputDestination($file_name);
    $GLOBALS['argv'] = [__DIR__ . '/../../php/boot-fs.php'];
    $return = Terminus::launchSelf("art unicorn > $file_name");
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, "<.'_.''") !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);
  }

  public function testSetCache() {
    //Default target
    $terminus = new Terminus();
    $root     = Terminus::getCache()->getRoot();
    $this->assertTrue(strpos($root, getenv('HOME')) !== false);

    //Giving no env var for explicitly set cache dir
    putenv('TERMINUS_CACHE_DIR=');
    $terminus->setCache();
    $root = Terminus::getCache()->getRoot();
    $this->assertTrue(strpos($root, getenv('HOME')) !== false);

    //Targeting a dir the Windows way
    exec('mkdir /tmp/out');
    $home = getenv('HOME');
    putenv('HOME=0');
    putenv('HOMEDRIVE=/tmp');
    putenv('HOMEPATH=out');
    $terminus->setCache();
    $root = Terminus::getCache()->getRoot();
    $this->assertTrue(strpos($root, '/tmp/out') !== false);

    //Clean-up
    putenv("HOME=$home");
    exec("rm -r /tmp/out");
  }

  public function testSetLogger() {
    // This test assumes that the debug output defaults to off.
    $file_name = getLogFileName();
    $message   = 'The sky is the daily bread of the eyes.';
    setOutputDestination($file_name);
    Terminus::getLogger()->debug($message);
    $output = retrieveOutput($file_name);
    $this->assertFalse(strpos($output, $message) !== false);
    Terminus::setLogger(['debug' => true, 'format' => 'json']);
    Terminus::getLogger()->debug($message);
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, $message) !== false);
    resetOutputDestination($file_name);
  }

  public function testSetOutputter() {
    // This test assumes that the format defaults to JSON.
    $formatter = Terminus::getOutputter()->getFormatter();
    $this->assertTrue(strpos(get_class($formatter), 'JSON') !== false);

    // This test assumes that the format defaults to Bash.
    Terminus::setOutputter('bash', 'php://stdout');
    $formatter = Terminus::getOutputter()->getFormatter();
    $this->assertTrue(strpos(get_class($formatter), 'Bash') !== false);

    Terminus::setOutputter('normal', 'php://stdout');
    $formatter = Terminus::getOutputter()->getFormatter();
    $this->assertTrue(strpos(get_class($formatter), 'Pretty') !== false);
  }

}
