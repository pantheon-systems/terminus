<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

use Pantheon\Terminus\Session\Session;
use Psr\Log\NullLogger;
use Terminus\Collections\Sites;
use Terminus\Models\Site;
use Terminus\Collections\Environments;
use Terminus\Models\Environment;
use Terminus\Models\Workflow;

/**
 * Base class for environment tests.
 */
abstract class EnvCommandTest extends CommandTestCase
{
    protected $session;
    protected $sites;
    protected $user;
    protected $logger;
    protected $command;

    /**
     * Setup the fixture.
     */
    protected function setUp()
    {
        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->method('get')
            ->willReturn($this->site);

        $this->site->environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->env = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->environments->method('get')
            ->willReturn($this->env);

        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
