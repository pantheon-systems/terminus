<?php


namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Env\InfoCommand;

class InfoCommandTest extends EnvCommandTest
{
    public function testGetInfo()
    {
        $data = ['foo' => 'bar', 'baz' => 'bop'];
        $this->environment->expects($this->once())
            ->method('serialize')
            ->willReturn($data);

        $this->command = new InfoCommand();
        $this->command->setSites($this->sites);
        $out = $this->command->getInfo('mysite.dev');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
