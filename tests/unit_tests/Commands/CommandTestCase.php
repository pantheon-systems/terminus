<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;

/**
 * Class CommandTestCase
 * @package Pantheon\Terminus\UnitTests\Commands
 */
abstract class CommandTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TerminusConfig
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
     * @var Environments
     */
    protected $environments;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        if (!$this->config) {
            $this->config = new TerminusConfig();
        }

        if (!$this->container) {
            $this->container = new Container();
        }

        // These are not used by every test but are useful for SiteAwareInterface commands. Which is a lot of them.
        // Use `$command->setSites($this->site());` after you create your command to test.
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environments->method('get')
            ->willReturn($this->environment);

        $this->site->method('getEnvironments')->willReturn($this->environments);

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
}
