<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Backup\InfoCommand;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
 */
class InfoCommandTest extends BackupCommandTest
{
    /**
     * @var array
     */
    protected $expected_data;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $sample_data = [
            'file' => 'file name',
            'size' => 'file size',
            'date' => 459880805,
            'expiry' => 3615640805,
            'initiator' => 'backup initiator',
        ];
        $url = 'https://url.to/backup.tgz';
        $this->expected_data = array_merge($sample_data, compact('url'));

        $this->backup->method('serialize')->willReturn($sample_data);
        $this->backup->method('getUrl')->willReturn($url);

        $this->command = new InfoCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:info command with file
     */
    public function testInfoBackupWithFile()
    {
        $test_filename = 'test.tar.gz';

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);

        $output = $this->command->info('mysite.dev', ['file' => $test_filename,]);
        $this->assertInstanceOf(PropertyList::class, $output);
        $this->assertEquals($this->expected_data, $output->getArrayCopy());
    }

    /**
     * Tests the backup:info command with an element
     */
    public function testInfoBackupWithElement()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup,]);

        $output = $this->command->info('mysite.dev', ['element' => 'db',]);
        $this->assertInstanceOf(PropertyList::class, $output);
        $this->assertEquals($this->expected_data, $output->getArrayCopy());
    }
}
