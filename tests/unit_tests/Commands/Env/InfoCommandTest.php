<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Env\InfoCommand;

/**
 * Class InfoCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Env\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class InfoCommandTest extends EnvCommandTest
{
    /**
     * Tests the env:info command
     */
    public function testInfo()
    {
        $data = ['foo' => 'bar', 'baz' => 'bop'];
        $this->environment->expects($this->once())
            ->method('serialize')
            ->willReturn($data);

        $this->command = new InfoCommand();
        $this->command->setSites($this->sites);
        $out = $this->command->info('mysite.dev');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
