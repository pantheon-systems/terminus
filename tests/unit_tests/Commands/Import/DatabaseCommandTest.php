<?php

namespace Pantheon\Terminus\UnitTests\Commands\Import;

use Pantheon\Terminus\Commands\Import\DatabaseCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class DatabaseCommandTest
 * Testing class for Pantheon\Terminus\Commands\Import\DatabaseCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Import
 */
class DatabaseCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new DatabaseCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
        $this->expectWorkflowProcessing();
    }
    
    /**
     * Exercises import:database command with a valid URL
     */
    public function testImportValidURL()
    {
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $valid_url = 'a_valid_url';

        $this->expectConfirmation();
        $this->environment->expects($this->once())
          ->method('importDatabase')
          ->with($this->equalTo($valid_url))
          ->willReturn($this->workflow);
        $this->site->expects($this->any())
          ->method('get')
          ->willReturn(null);
        $this->logger->expects($this->once())
          ->method('log')->with(
              $this->equalTo('notice'),
              $this->equalTo('Imported database to {site}.{env}.')
          );

        $out = $this->command->import("$site_name.{$this->environment->id}", $valid_url);
        $this->assertNull($out);
    }
}
