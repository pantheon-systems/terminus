<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\GetCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\Request;

/**
 * Class GetCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\GetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
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
        $this->command->setContainer($this->getContainer());
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
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);

        $this->backup->expects($this->once())
            ->method('getArchiveURL')
            ->willReturn($test_download_url);

        $output = $this->command->get('mysite.dev', ['file' => $test_filename,]);
        $this->assertEquals($output, $test_download_url);
    }

    /**
     * Tests the backup:get command with an element
     */
    public function testGetBackupWithElement()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup]);

        $this->backup->expects($this->once())
            ->method('getArchiveURL')
            ->willReturn('http://download');

        $output = $this->command->get('mysite.dev', ['element' => 'db',]);
        $this->assertEquals($output, 'http://download');
    }

    /**
     * Tests the backup:get command with file that doesn't exist
     */
    public function testGetBackupWithInvalidFile()
    {
        $bad_file_name = 'no-file.tar.gz';

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($this->equalTo($bad_file_name))
            ->will($this->throwException(new TerminusNotFoundException()));

        $this->setExpectedException(TerminusNotFoundException::class);

        $out = $this->command->get('mysite.dev', ['file' => $bad_file_name,]);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:get command when there are no backups to get
     */
    public function testGetBackupNoBackups()
    {
        $element = 'some_element';
        $site = 'site';
        $this->environment->id = 'env';

        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with($this->equalTo($element))
            ->willReturn([]);
        $this->backup->expects($this->never())
            ->method('getArchiveURL');
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site);
        $this->setExpectedException(
            TerminusNotFoundException::class,
            "No backups available. Create one with `terminus backup:create $site.{$this->environment->id}`"
        );

        $out = $this->command->get("$site.{$this->environment->id}", compact('element'));
        $this->assertNull($out);
    }

    /**
     * Tests the backup:get command when saving the backup to a file
     */
    public function testGetBackupToFile()
    {
        $test_filename = 'test.tar.gz';
        $test_download_url = 'http://download';
        $test_save_path = '/tmp/file.tar.gz';
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);
        $this->backup->expects($this->once())
            ->method('getArchiveURL')
            ->willReturn($test_download_url);
        $request->expects($this->once())
            ->method('download')
            ->with(
                $this->equalTo($test_download_url),
                $this->equalTo($test_save_path)
            );

        $this->command->setRequest($request);
        $out = $this->command->get('mysite.dev', ['file' => $test_filename, 'to' => $test_save_path,]);
        $this->assertNull($out);
    }
}
