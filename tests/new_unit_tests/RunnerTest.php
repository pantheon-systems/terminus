<?php

namespace Pantheon\Terminus\UnitTests;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Terminus;

/**
 * Testing class for Pantheon\Terminus\Terminus
 */
class RunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Terminus
     */
    private $application;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Runner
     */
    private $runner;

    /**
     * Creates oft-used objects
     */
    public function __construct($name = null, array $data = [], $dataName = null)
    {
        parent::__construct($name, $data, $dataName);
        $this->config = new Config();
        $this->application = new Terminus('Terminus', $this->config->get('version'), $this->config);
        $this->runner = new Runner(['application' => $this->application, 'config' => $this->config,]);
    }

    /**
     * Tests the Terminus constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $runner = new Runner(['application' => $this->application, 'config' => $this->config,]);
        $this->assertAttributeInstanceOf('Symfony\Component\Console\Application', 'application', $runner);
        $this->assertAttributeInstanceOf('Pantheon\Terminus\Config', 'config', $runner);
        $this->assertAttributeInstanceOf('Robo\Runner', 'runner', $runner);
    }

    /**
     * Tests the run function
     *
     * @return void
     * @expectedException \Error
     */
    public function testRun()
    {
        $runner = new Runner();

        $status_code = $runner->run([null, '-V', '--quiet']);
        $this->assertEquals(0, $status_code);

        $status_code = $runner->run([null, 'DNE', '--quiet']);
        $this->assertEquals(1, $status_code);
    }
}
