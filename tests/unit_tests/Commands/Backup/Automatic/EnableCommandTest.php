<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup\Automatic;

use Pantheon\Terminus\Commands\Backup\Automatic\EnableCommand;
use Pantheon\Terminus\UnitTests\Commands\Backup\BackupCommandTest;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class EnableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\Automatic\EnableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup\Automatic
 */
class EnableCommandTest extends BackupCommandTest
{
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
        $this->command = new EnableCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests the backup:automatic:enable command
     */
    public function testSetAutomaticSchedule()
    {
        $this->environment->id = 'some_env';
        $schedule_info = ['day' => 'Caturday',];

        $this->backups->expects($this->once())
            ->method('setBackupSchedule')
            ->with($this->equalTo($schedule_info))
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Backup schedule successfully set.')
            );

        $out = $this->command->enableSchedule('mysite.some_env', $schedule_info);
        $this->assertNull($out);
    }
}
