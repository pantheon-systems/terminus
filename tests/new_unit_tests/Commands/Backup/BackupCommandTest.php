<?php
namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

use Pantheon\Terminus\Session\Session;
use Psr\Log\NullLogger;
use Terminus\Collections\Sites;
use Terminus\Models\Site;
use Terminus\Collections\Environments;
use Terminus\Models\Environment;
use Terminus\Collections\Backups;
use Terminus\Models\Backup;

/**
 * @property \PHPUnit_Framework_MockObject_MockObject sites
 */
abstract class BackupCommandTest extends CommandTestCase
{
    protected $session;
    protected $sites;
    protected $user;
    protected $logger;
    protected $command;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->backups = $this->getMockBuilder(Backups::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->backups = $this->backups;

        $this->backup = $this->getMockBuilder(Backup::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
