<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Style\TerminusStyle;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\Input;
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
     * @var Environment
     */
    protected $environment;
    /**
     * @var Environments
     */
    protected $environments;
    /**
     * @var ArrayInput
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var Site
     */
    protected $site2;
    /**
     * @var Sites
     */
    protected $sites;
    /**
     * @var TerminusStyle
     */
    protected $style;

    /**
     * @return TerminusConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param TerminusConfig $config
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
            $this->setConfig(
                $this->getMockBuilder(TerminusConfig::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );
        }
        if (!$this->container) {
            $this->setContainer(
                $this->getMockBuilder(Container::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );
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
        $this->environments->method('getCollectedClass')->willReturn(Environment::class);

        $this->environments->method('get')
            ->willReturn($this->environment);

        $this->site->method('getEnvironments')->willReturn($this->environments);
        $this->site->id = 'abc';

        $this->site2 = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site2->id = 'def';

        // Always say yes to confirmations
        $this->input = $this->getMockBuilder(Input::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sites->method('getCollectedClass')->willReturn(Site::class);

        $this->sites->method('get')
            ->willReturn($this->site);

        $this->output = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // A lot of commands output to a logger.
        // To use this call `$command->setLogger($this->logger);` after you create your command to test.
        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    /**
     * Responds to the confirmation prompt
     *
     * @deprecated 1.0.1 This is a test for the incorrect way to do this and will be removed in the future.
     *
     * @todo Remove this when removing TerminusCommand::confirm()
     */
    protected function expectConfirmation()
    {
        $this->input->expects($this->once())
            ->method('hasOption')
            ->with('yes')
            ->willReturn(true);
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('yes')
            ->willReturn(true);
    }
}
