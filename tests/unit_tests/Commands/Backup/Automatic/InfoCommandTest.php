<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup\Automatic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Backup\Automatic\InfoCommand;
use Pantheon\Terminus\UnitTests\Commands\Backup\BackupCommandTest;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\Automatic\GetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup\Automatic
 */
class InfoCommandTest extends BackupCommandTest
{

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new InfoCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:automatic:info command when the schedule is set
     */
    public function testAutomaticBackupSchedule()
    {
        $this->environment->id = 'scheduled';
        $schedule_info = [
            'daily_backup_hour' => '13 UTC',
            'weekly_backup_day' => 'Caturday',
        ];

        $this->backups->expects($this->once())
            ->method('getBackupSchedule')
            ->with()
            ->willReturn($schedule_info);

        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->getSchedule('mysite.scheduled');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($out->getArrayCopy(), $schedule_info);
    }

    /**
     * Tests the backup:automatic:info command when the schedule is not set
     */
    public function testAutomaticBackupScheduleNotSet()
    {
        $this->environment->id = 'scheduled';
        $schedule_info = [
          'daily_backup_hour' => null,
          'weekly_backup_day' => null,
        ];

        $this->backups->expects($this->once())
          ->method('getBackupSchedule')
          ->with()
          ->willReturn($schedule_info);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Backups are not currently scheduled to be run.')
            );

        $out = $this->command->getSchedule('mysite.scheduled');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($out->getArrayCopy(), $schedule_info);
    }
}
