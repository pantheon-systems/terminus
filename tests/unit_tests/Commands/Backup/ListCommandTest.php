<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Backup\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
 */
class ListCommandTest extends BackupCommandTest
{
    /**
     * @var array
     */
    protected $sample_data;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sample_data = ['data', 'data2'];

        $this->backup->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->sample_data);

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
            ->with()
            ->willReturn([$this->backup,]);

        $out = $this->command->listBackups('mysite.dev');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$this->sample_data,], $out->getArrayCopy());
    }

    /**
     * Tests the backup:list command with 'db' element
     */
    public function testListBackupsWithDatabaseElement()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with($this->equalTo('database'))
            ->willReturn([$this->backup,]);

        $out = $this->command->listBackups('mysite.dev', 'all', ['element' => 'db',]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$this->sample_data,], $out->getArrayCopy());
    }

    /**
     * Tests the backup:list command when the options array appears in the second parameter
     */
    public function testListBackupsWithOptionsInSecondParameter()
    {
        $element = "don't care";

        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with($this->equalTo($element))
            ->willReturn([$this->backup,]);


        $out = $this->command->listBackups('mysite.dev', compact('element'));
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$this->sample_data,], $out->getArrayCopy());
    }

    /**
     * Tests the backup:list command with 'files' element
     */
    public function testListBackupsWithSomeOtherElement()
    {
        $element = 'files';

        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with($this->equalTo($element))
            ->willReturn([$this->backup,]);


        $out = $this->command->listBackups('mysite.dev', 'all', compact('element'));
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$this->sample_data,], $out->getArrayCopy());
    }
}
