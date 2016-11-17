<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Import\ImportCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Import\ImportCommand
 */
class ImportCommandTest extends CommandTestCase
{

    /**
     * Test suite setup
     *
     * @return void
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new ImportCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    
    /**
     * Exercises site:import command with a valid url
     *
     * @return void
     *
     */
    public function testSiteImportValidURL()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflow->expects($this->once())->method('wait')->willReturn(true);

        $this->environment->expects($this->once())->method('import')
            ->with($this->equalTo('a-valid-url'))->willReturn($workflow);
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Imported site onto Pantheon')
            );

        $this->command->import('dummy-site', 'a-valid-url');
    }
}
