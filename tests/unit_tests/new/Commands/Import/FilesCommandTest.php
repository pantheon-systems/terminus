<?php

namespace Pantheon\Terminus\UnitTests\Commands\Import;

use Pantheon\Terminus\Commands\Import\FilesCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Import\FilesCommand
 */
class FilesCommandTest extends CommandTestCase
{

    /**
     * Test suite setup
     *
     * @return void
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new FilesCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    
    /**
     * Exercises site:import command with a valid url
     *
     * @return void
     *
     */
    public function testSiteImportFilesValidURL()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $workflow->expects($this->once())->method('wait')->willReturn(true);

        $this->environment->expects($this->once())->method('importFiles')
            ->with($this->equalTo('a-valid-url'))->willReturn($workflow);
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Importing files to "dev"')
            );

        $this->command->importFiles('dummy-site', 'a-valid-url');
    }
}
