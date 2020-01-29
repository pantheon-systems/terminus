<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Backup\ListCommand;
use Pantheon\Terminus\Models\Backup;

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

        $this->sample_data = [
            'data',
            'data2',
        ];
        $this->backups->method('getCollectedClass')->willReturn(Backup::class);

        $this->backups->expects($this->once())
            ->method('filterForFinished')
            ->willReturn($this->backups);
        $this->backups->expects($this->once())
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
        $out = $this->command->listBackups('mysite.dev');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($this->sample_data, $out->getArrayCopy());
    }

    /**
     * Tests the backup:list command when the options array appears in the second parameter
     */
    public function testListBackupsWithOptionsInSecondParameter()
    {
        $element = "don't care";

        $this->backups->expects($this->once())
            ->method('filterForElement')
            ->with($element)
            ->willReturn($this->backups);

        $out = $this->command->listBackups('mysite.dev', compact('element'));
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($this->sample_data, $out->getArrayCopy());
    }

    /**
     * Tests the backup:list command with 'files' element
     */
    public function testListBackupsWithSomeOtherElement()
    {
        $element = 'files';

        $this->backups->expects($this->once())
            ->method('filterForElement')
            ->with($element)
            ->willReturn($this->backups);

        $out = $this->command->listBackups('mysite.dev', compact('element'));
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($this->sample_data, $out->getArrayCopy());
    }
}
