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
    protected $command;

    /**
     * Setup the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
