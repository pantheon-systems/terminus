<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Import\SiteCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteCommandTest
 * Testing class for Pantheon\Terminus\Commands\Import\SiteCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class SiteCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new SiteCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    
    /**
     * Exercises site:import command with a valid URL
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
