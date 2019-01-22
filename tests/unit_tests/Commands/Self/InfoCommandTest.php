<?php

namespace Pantheon\Terminus\UnitTests\Commands\Self;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Self\InfoCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Self\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Self
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * Tests the self:info command
     */
    public function testInfo()
    {
        $output_data = [
            'php_binary_path' => '*PHPBINARY*',
            'php_version' => '*PHPVERSION*',
            'php_ini' => '*PHPINI*',
            'project_config_path' => '*CONFIGDIR*',
            'terminus_path' => '*TERMINUSROOT*',
            'terminus_version' => '*TERMINUSVERSION*',
            'os_version' => '*OSVERSION*',
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

        $this->assertInstanceOf(PropertyList::class, $info);
        $this->assertEquals($output_data, $info->getArrayCopy());
    }
}
