<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Terminus;
use Psr\Log\LoggerInterface;
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
use Terminus\Models\User;
use VCR\VCR;

abstract class CommandTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    protected $config;


    /**
     * @var Sites
     */
    protected $sites;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var LoggerInterface
     */
    protected $logger;


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
     * @inheritdoc
     */
    protected function setUp()
    {
        if (!$this->config) {
            $this->config = new Config();
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

        $this->user= $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user->method('sitesCollection')
            ->willReturn($this->sites);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->method('getUser')
            ->willReturn($this->user);

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
