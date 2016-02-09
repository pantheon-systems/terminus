<?php

use Terminus\Dispatcher;

/**
 * Testing class for Terminus
 */
class TerminusTest extends PHPUnit_Framework_TestCase {

  public function testConstruct() {
    $terminus    = new Terminus();
    $this->assertTrue(get_class($terminus) == 'Terminus');
  }

  /**
   * @expectedException \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage Unknown config option "{key}".
   */
  public function testGetConfig() {
    $format = Terminus::getConfig('format');
    $this->assertTrue(
      in_array($format, ['normal', 'json', 'silent', 'bash'])
    );

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
