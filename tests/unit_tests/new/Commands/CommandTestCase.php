<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Terminus;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Robo\Robo;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Terminus\Collections\Environments;
use Terminus\Collections\Sites;
use Terminus\Models\Environment;
use Terminus\Models\Site;
use VCR\VCR;

abstract class CommandTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Terminus
     */
    protected $app;
    /**
     * @var string
     */
    protected $status_code;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var Runner
     */
    protected $runner;
    /**
     * @var ArrayInput
     */
    protected $input;

    /**
     * @var Sites
     */
    protected $sites;

    /**
     * @var Site
     */
    protected $site;
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @return Terminus
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param Terminus $app
     * @return $this
     */
    public function setApp($app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     * @return CommandTestCase
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * @param Runner $runner
     * @return CommandTestCase
     */
    public function setRunner($runner)
    {
        $this->runner = $runner;
        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Convert the output of a command to an easily digested string.
     * @return string|OutputInterface
     */
    public function fetchTrimmedOutput()
    {
        if (get_class($this->output) == BufferedOutput::class) {
            return trim($this->getOutput()->fetch());
        }
        return $this->getOutput();
    }

    /**
     * @param OutputInterface $output
     * @return CommandTestCase
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param mixed $container
     * @return CommandTestCase
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * @param mixed $status_code
     * @return CommandTestCase
     */
    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param mixed $input
     * @return CommandTestCase
     */
    public function setInput($input)
    {
        $this->input = new ArrayInput($input);
        return $this;
    }

    /**
     * Run the command and capture the exit code.
     *
     * @return $this
     */
    public function runCommand()
    {
        $this->status_code = $this->runner->run($this->input, $this->output);
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        if (!$this->config) {
            $this->config = new Config();
        }

        if (!$this->app) {
            $this->app = new Terminus('Terminus', $this->config->get('version'), $this->config);
        }

        if (!$this->container) {
            $this->container = new Container();
        }

        if (!$this->output) {
            $this->output = new BufferedOutput();
        }

        if (!$this->input) {
            $this->input = new ArrayInput([]);
        }
        // Configuring the dependency-injection container
        Robo::configureContainer(
            $this->container,
            $this->app,
            $this->config,
            $this->input,
            $this->output
        );

        // Set the application dispatcher (required when using Robo::configureContainer)
        $this->app->setDispatcher($this->container->get('eventDispatcher'));

        if (!$this->runner) {
            $this->runner = new Runner($this->container);
        }

        if (!empty($mode = $this->config->get('vcr_mode'))) {
            VCR::configure()->setMode($mode);
        }

        // These are not used by every test but are useful for SiteAwareInterface commands. Which is a lot of them.
        // Use `$command->setSites($this->site());` after you create your command to test.
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->environments->method('get')
            ->willReturn($this->environment);

        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->method('get')
            ->willReturn($this->site);

        // A lot of commands output to a logger.
        // To use this call `$command->setLogger($this->logger);` after you create your command to test.
        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    /**
     * Expose a protected method to testing
     * https://rjzaworski.com/2012/04/testing-protected-methods-in-php
     *
     * @param mixed  $obj    Object containing the protected method to be called
     * @param string $method Name of protected method
     * @param array  $args   Method arguments
     *
     * @return mixed
     */
    protected function protectedMethodCall($obj, $method, $args = array())
    {
        $method = new ReflectionMethod(get_class($obj), $method);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
