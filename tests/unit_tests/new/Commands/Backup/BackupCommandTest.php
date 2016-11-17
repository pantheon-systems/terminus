<?php
namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

use Terminus\Collections\Backups;
use Terminus\Models\Backup;
use Pantheon\Terminus\Models\Workflow;

/**
 * @property \PHPUnit_Framework_MockObject_MockObject sites
 */
abstract class BackupCommandTest extends CommandTestCase
{
    /**
     * @var Backup
     */
    protected $backup;
    /**
     * @var Backups
     */
    protected $backups;
    /**
     * @var Workflow
     */
    protected $workflow;

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

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
