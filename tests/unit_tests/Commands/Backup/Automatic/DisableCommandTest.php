<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup\Automatic;

use Pantheon\Terminus\Commands\Backup\Automatic\DisableCommand;
use Pantheon\Terminus\UnitTests\Commands\Backup\BackupCommandTest;

/**
 * Class DisableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\Automatic\DisableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup\Automatic
 */
class DisableCommandTest extends BackupCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new DisableCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:automatic:disable command
     */
    public function testDisableBackupSchedule()
    {
        $this->environment->id = 'scheduled';

        $this->backups->expects($this->once())
            ->method('cancelBackupSchedule')
            ->with();

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Backup schedule successfully canceled.')
            );

        $out = $this->command->disableSchedule('mysite.scheduled');
        $this->assertNull($out);
    }
}
