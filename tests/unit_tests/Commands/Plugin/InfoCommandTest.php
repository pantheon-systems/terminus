<?php

namespace Pantheon\Terminus\UnitTests\Commands\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Plugin\InfoCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Plugin\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Plugin
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * Tests the plugin:info command
     */
    public function testInfo()
    {
        $output_data = [
            'name' => '*NAME*',
            'description' => '*DESCRIPTION*',
        ];

        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($output_data);

        $command = new InfoCommand();
        $command->setConfig($this->config);
        $info = $command->info();

        $this->assertInstanceOf(RowsOfFields::class, $info);
        $this->assertEquals($output_data, $info->getArrayCopy());
    }
}
