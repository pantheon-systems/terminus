<?php
namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\GetCommand;
use Terminus\Exceptions\TerminusNotFoundException;

/**
 * Testing class for Pantheon\Terminus\Commands\Backup\GetCommand
 */
class GetCommandTest extends BackupCommandTest
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new GetCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:get command with file
     */
    public function testGetBackupWithFile()
    {
        $test_filename = 'test.tar.gz';
        $test_download_url = 'http://download';

        $this->backups->expects($this->once())
            ->method('getValidElements')
            ->with()
            ->willReturn([]);

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);

        $this->backup->expects($this->once())
            ->method('getUrl')
            ->willReturn($test_download_url);

        $output = $this->command->getBackup('mysite.dev', $test_filename);
        $this->assertEquals($output, $test_download_url);
    }

    /**
     * Tests the backup:get command with an element
     */
    public function testGetBackupWithElement()
    {
        $this->backups->expects($this->once())
            ->method('getValidElements')
            ->with()
            ->willReturn(['db', 'database', 'code', 'files',]);

        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup]);

        $this->backup->expects($this->once())
            ->method('getUrl')
            ->willReturn('http://download');

        $output = $this->command->getBackup('mysite.dev', 'db');
        $this->assertEquals($output, 'http://download');
    }

    /**
     * Tests the backup:get command with file that doesn't exist
     */
    public function testGetBackupWithInvalidFile()
    {
        $bad_file_name = 'no-file.tar.gz';

        $this->backups->expects($this->once())
            ->method('getValidElements')
            ->with()
            ->willReturn([]);

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($this->equalTo($bad_file_name))
            ->will($this->throwException(new TerminusNotFoundException()));

        $this->setExpectedException(TerminusNotFoundException::class);

        $this->command->getBackup('mysite.dev', 'no-file.tar.gz');
    }
}
