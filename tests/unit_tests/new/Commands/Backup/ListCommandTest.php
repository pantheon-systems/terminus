<?php
namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\ListCommand;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Testing class for Pantheon\Terminus\Commands\Backup\ListCommand
 */
class ListCommandTest extends BackupCommandTest
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
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
     *
     * @return void
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
     *
     * @return void
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
