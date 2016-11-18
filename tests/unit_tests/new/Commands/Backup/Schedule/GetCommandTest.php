<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup\Schedule;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Backup\Schedule\GetCommand;
use Pantheon\Terminus\UnitTests\Commands\Backup\BackupCommandTest;

/**
 * Class GetCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\Schedule\GetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup\Schedule
 */
class GetCommandTest extends BackupCommandTest
{

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new GetCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:schedule:get command when the schedule is set
     */
    public function testGetBackupSchedule()
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
     * Tests the backup:schedule:get command when the schedule is not set
     */
    public function testGetBackupScheduleNotSet()
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
