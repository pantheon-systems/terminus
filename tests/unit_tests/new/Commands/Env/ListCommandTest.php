<?php


namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Env\ListCommand;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Models\Environment;

/**
 * Class ListCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Env\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class ListCommandTest extends EnvCommandTest
{
    /**
     * Tests the env:list command
     */
    public function testListEnvs()
    {
        $data = [
            ['foo' => 'bar', 'baz' => 'bop'],
            ['foo' => 'abc', 'baz' => 'def'],
        ];

        $envs = [];
        foreach ($data as $env) {
            $mock = $this->getMockBuilder(Environment::class)
                ->disableOriginalConstructor()
                ->getMock();

            $mock->expects($this->once())
                ->method('serialize')
                ->willReturn($env);
            $envs[] = $mock;
        }
        $this->environments->expects($this->once())
            ->method('all')
            ->willReturn($envs);

        $this->command = new ListCommand();
        $this->command->setSites($this->sites);
        $out = $this->command->listEnvs('mysite');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
