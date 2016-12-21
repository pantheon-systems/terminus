<?php

namespace Pantheon\Terminus\UnitTests\Commands\Self;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Self\ConfigDumpCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ConfigDumpCommandTest
 * Testing class for Pantheon\Terminus\Commands\Self\ConfigDumpCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Self
 */
class ConfigDumpCommandTest extends CommandTestCase
{
    /**
     * @var TerminusConfig
     */
    protected $config;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ConfigDumpCommand();
        $this->command->setConfig($this->config);
    }

    /**
     * Tests the self:config-dump command
     */
    public function testConfigDump()
    {
        $config_keys = [
            'php_binary_path',
            'php_version',
            'php_ini',
            'project_config_path',
            'terminus_path',
            'terminus_version',
            'os_version',
        ];
        $result_data = [];

        $this->config->expects($this->once())
            ->method('keys')
            ->with()
            ->willReturn($config_keys);

        $i = 0;
        $rand = rand();
        foreach ($config_keys as $key) {
            $this->config->method('getConstantFromKey')
                ->willReturn($rand);
            $this->config->method('get')
                ->willReturn($rand);
            $this->config->method('getSource')
                ->willReturn($rand);
            $result_data[] = ['key' => $key, 'env' => $rand, 'value' => $rand, 'source' => $rand,];
        }

        $out = $this->command->dumpConfig();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($result_data, $out->getArrayCopy());
    }
}
