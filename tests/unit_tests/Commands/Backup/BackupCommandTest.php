<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Models\Backup;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class BackupCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
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
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->backups = $this->getMockBuilder(Backups::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->method('getBackups')->willReturn($this->backups);

        $this->backup = $this->getMockBuilder(Backup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
