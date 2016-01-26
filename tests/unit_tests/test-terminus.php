<?php

/**
 * Testing class for Terminus
 */
class TerminusTest extends PHPUnit_Framework_TestCase {

  public function testConstruct() {
    $terminus = new Terminus();
    $this->assertTrue(get_class($terminus) == 'Terminus');
  }

  public function testAddCommand() {
    Terminus::addCommand('auth', 'AuthCommand');
  }

  public function testGetCache() {
    $cache   = Terminus::getCache();
    $session = $cache->getData('session');
    $this->assertEquals($session->user_uuid, '0ffec038-4410-43d0-a404-46997f672d7a');
  }

  public function testGetConfig() {
    $format = Terminus::getConfig('format');
    $this->assertEquals($format, 'normal');

    $config = Terminus::getConfig();
    $this->assertInternalType('array', $config);
  }

  public function testGetLogger() {
    $logger = Terminus::getLogger();
    $this->assertTrue(strpos(get_class($logger), 'Logger') !== false);
  }

  public function testGetOutputter() {
    $outputter = Terminus::getOutputter();
    $this->assertTrue(strpos(get_class($outputter), 'Outputter') !== false);
  }

  public function testGetRootCommand() {
    $root_command = Terminus::getRootCommand();
    $this->assertTrue(
      strpos(get_class($root_command), 'RootCommand') !== false
    );
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
    setOutputDestination($file_name);
    $return = Terminus::launch("ls tests/ > $file_name");
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, 'unit_tests') !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);
  }

  public function testLaunchSelf() {
    $file_name = '/tmp/output';
    setOutputDestination($file_name);
    $return = Terminus::launchSelf("art unicorn > $file_name");
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, "<.'_.''") !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);
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

    Terminus::setOutputter('normal');
    $formatter = Terminus::getOutputter()->getFormatter();
    $this->assertTrue(strpos(get_class($formatter), 'Pretty') !== false);
  }

}
