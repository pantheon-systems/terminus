<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
 */
class ListCommandTest extends BackupCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new ListCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:list command without any elements
     */
    public function testListBackups()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->willReturn([$this->backup]);

        $this->command->listBackups('mysite.dev');
    }

    /**
     * Tests the backup:list command with 'db' element
     */
    public function testListBackupsWithDatabaseElement()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup]);

        $this->command->listBackups('mysite.dev', 'db');
    }
}
