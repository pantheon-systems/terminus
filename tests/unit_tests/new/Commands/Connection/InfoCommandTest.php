<?php

namespace Pantheon\Terminus\UnitTests\Commands\Connection;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Connection\InfoCommand;
use Prophecy\Prophet;
use Terminus\Collections\Sites;
use Terminus\Exceptions\TerminusException;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Connection\InfoCommand
 */
class InfoCommandTest extends ConnectionCommandTest
{
    /**
     * Test suite setup
     *
     * @return void
     */
    protected function setup()
    {
        parent::setUp();

        $this->command = new InfoCommand($this->getConfig());

        // use the basic mocked sites from CommandTestCase
        $this->command->setSites($this->sites);
    }

    /**
     * Ensure connection:info delegates to the Environment::connectionInfo()
     *
     * @return void
     */
    public function testConnectionInfo()
    {
        // should delegate to the environment model appropriately
        $this->environment->expects($this->once())->method('connectionInfo')
            ->willReturn(['foo' => 'bar']);

        // command execution
        $out = $this->command->connectionInfo('dummy-site.dev');

        // should return the correct type
        $this->assertInstanceOf(PropertyList::class, $out);
    }
}
