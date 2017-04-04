<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Backup\InfoCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
 */
class InfoCommandTest extends BackupCommandTest
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sample_data = [
            'file' => 'file name',
            'size' => 'file size',
            'date' => 459880805,
            'expiry' => 3615640805,
            'initiator' => 'backup initiator',
        ];
        $this->backup->method('serialize')->willReturn($this->sample_data);

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
        $this->assertEquals($this->sample_data, $output->getArrayCopy());
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
        $this->assertEquals($this->sample_data, $output->getArrayCopy());
    }
}
