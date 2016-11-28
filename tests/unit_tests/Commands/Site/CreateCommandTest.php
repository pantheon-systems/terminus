<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Workflow;

/**
 * Test suite class for Pantheon\Terminus\Commands\Site\CreateCommand
 *
 * TODO: Update this when Org and Upstreams are both accessible through DI
 */
/**
class CreateCommandTest extends CommandTestCase
{

    /**
     * Test suite setup
     *
     * @return void
     * /
    protected function setup()
    {
        parent::setUp();
        $this->command = new CreateCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises site:create command
     * /
    public function testCreateSite()
    {
        $workflow_options = [
            'label'     => 'valid_label',
            'site_name' => 'valid_site_name'
        ];
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->setMethods(['wait'])
            ->getMock();
        $workflow2 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->setMethods(['wait'])
            ->getMock();

        $workflow->expects($this->once())->method('wait')->willReturn(true);
        $workflow2->expects($this->once())->method('wait')->willReturn(true);

        $this->sites->expects($this->once())
            ->method('create')
            ->with($this->equalTo($workflow_options))
            ->willReturn($workflow);

        $this->site->expects($this->once())
            ->method('deployProduct')
            ->with($this->equalTo('a_valid_upstream'))
            ->willReturn($workflow2);
        $this->command->create('valid_site_name', 'valid_label', 'a_valid_upstream');
    }
}
*/
