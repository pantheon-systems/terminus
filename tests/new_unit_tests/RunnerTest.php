<?php

namespace Pantheon\Terminus\UnitTests;

use League\Container\Container;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Terminus;
use Robo\Robo;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var Container
     */
    private $container;
    /**
     * @var OutputInterface
     */
    private $output;
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
        // Initializing the Terminus application
        $this->config = new Config();
        $this->application = new Terminus('Terminus', $this->config->get('version'), $this->config);

        // Configuring the dependency-injection container
        $this->container = new Container();
        $input = new ArgvInput($_SERVER['argv']);
        $this->output = new NullOutput();
        $roboConfig = new \Robo\Config(); // TODO: make Terminus Config extend \Robo\Config and use $config here
        Robo::configureContainer($this->container, $roboConfig, $input, $this->output, $this->application);

        // Instantiating the Runner
        $this->runner = new Runner($this->container);
    }

    /**
     * Tests the Terminus constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $runner = new Runner($this->container);
        $this->assertAttributeInstanceOf('Robo\Runner', 'runner', $runner);
    }

    /**
     * Tests the run function
     *
     * @return void
     */
    public function testRun()
    {
        $input = new ArgvInput([null, '-V', '--quiet']);
        $status_code = $this->runner->run($input, $this->output);
        $this->assertEquals(0, $status_code);

        $input = new ArgvInput([null, 'DNE', '--quiet']);
        $status_code = $this->runner->run($input, $this->output);
        $this->assertEquals(1, $status_code);
    }
}
