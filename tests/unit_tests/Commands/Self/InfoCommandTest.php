<?php

namespace Pantheon\Terminus\UnitTests\Commands\Self;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Self\InfoCommand;
use Robo\Config;
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
        $command = new InfoCommand();
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config_data = [
            'php' => '*PHPBINARY*',
            'php_version' => '*PHPVERSION*',
            'php_ini' => '*PHPINI*',
            'config_dir' => '*CONFIGDIR*',
            'root' => '*TERMINUSROOT*',
            'version' => '*TERMINUSVERSION*',
            'os_version' => '*OSVERSION*',
        ];
        $output_data = [
            'php_binary_path' => '*PHPBINARY*',
            'php_version' => '*PHPVERSION*',
            'php_ini' => '*PHPINI*',
            'project_config_path' => '*CONFIGDIR*',
            'terminus_path' => '*TERMINUSROOT*',
            'terminus_version' => '*TERMINUSVERSION*',
            'os_version' => '*OSVERSION*',
        ];

        $i = 0;
        foreach ($config_data as $key => $val) {
            $this->config->expects($this->at($i++))
                ->method('get')
                ->with($key)
                ->willReturn($val);
        }
        $command->setConfig($this->config);
        $info = $command->info();
        $this->assertInstanceOf(PropertyList::class, $info);
        $this->assertEquals($output_data, $info->getArrayCopy());
    }
}
