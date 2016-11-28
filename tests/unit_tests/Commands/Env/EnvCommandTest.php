<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

use Psr\Log\NullLogger;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class EnvCommandTest
 * Base class for the environment tests
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
abstract class EnvCommandTest extends CommandTestCase
{
    protected $session;
    protected $user;
    protected $logger;
    protected $command;
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
