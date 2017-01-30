<?php

namespace Pantheon\Terminus\UnitTests\Commands\NewRelic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\NewRelic\InfoCommand;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\NewRelic\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\NewRelic
 */
class InfoCommandTest extends NewRelicCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InfoCommand();
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the new-relic:info command
     */
    public function testInfo()
    {
        $data = ['name' => 'Name', 'info' => 'Info',];

        $this->new_relic->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);

        $out = $this->command->info('mysite');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
