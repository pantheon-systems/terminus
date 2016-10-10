<?php
namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\GetCommand;
use Pantheon\Terminus\Config;
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
     *
     * @return void
     */
    public function testGotBackupWithFile()
    {
        $test_filename = 'test.tar.gz';
        $test_download_url = 'http://download';

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);

        $this->backup->expects($this->once())
            ->method('getUrl')
            ->willReturn($test_download_url);

        $output = $this->command->gotBackup('mysite.dev', $test_filename);
        $this->assertEquals($output, $test_download_url);
    }

    /**
     * Tests the backup:get command with an element
     *
     * @return void
     */
    public function testGotBackupWithElement()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup]);

        $this->backup->expects($this->once())
            ->method('getUrl')
            ->willReturn('http://download');

        $output = $this->command->gotBackup('mysite.dev', 'db');
        $this->assertEquals($output, 'http://download');
    }

    /**
     * Tests the backup:get command with file that doesn't exist
     *
     * @return void
     */
    public function testGotBackupWithInvalidFile()
    {
        $this->setExpectedException(TerminusNotFoundException::class);

        $this->backups
            ->method('getBackupByFileName')
            ->will($this->throwException(new TerminusNotFoundException));

        $this->command->gotBackup('mysite.dev', 'no-file.tar.gz');
    }
}
