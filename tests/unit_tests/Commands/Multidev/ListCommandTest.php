<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Multidev\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Multidev
 */
class ListCommandTest extends MultidevCommandTest
{
    /**
     * @var array
     */
    protected $data;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->data = [
            'id' => 'testing',
            'created' => '1984/07/28 16:40',
            'domain' => 'domain',
            'on_server_development' => true,
            'locked' => false,
            'initialized' => true,
        ];

        $this->logger->expects($this->never())
            ->method($this->anything());
        $this->environments->expects($this->once())
            ->method('filterForMultidev')
            ->willReturn($this->environments);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the multidev:list command when there are no multidev environments
     */
    public function testMultidevListNotEmpty()
    {
        $this->environments->expects($this->once())
            ->method('serialize')
            ->willReturn($this->data);

        $out = $this->command->listMultidevs('my_site');
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);

        $this->assertEquals($this->data, $out->getArrayCopy());
    }
}
