<?php

namespace Pantheon\Terminus\UnitTests\Commands\NewRelic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\NewRelic\StatusCommand;

/**
 * Class StatusCommandTest
 * Testing class for Pantheon\Terminus\Commands\NewRelic\StatusCommand
 * @package Pantheon\Terminus\UnitTests\Commands\NewRelic
 */
class StatusCommandTest extends NewRelicCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new StatusCommand();
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the new-relic:status command
     */
    public function testStatus()
    {
        $data = ['name' => 'Name', 'status' => 'Status',];

        $this->new_relic->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);

        $out = $this->command->status('mysite');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
