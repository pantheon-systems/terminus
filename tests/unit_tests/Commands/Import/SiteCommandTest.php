<?php

namespace Pantheon\Terminus\UnitTests\Commands\Import;

use Pantheon\Terminus\Commands\Import\SiteCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SiteCommandTest
 * Testing class for Pantheon\Terminus\Commands\Import\SiteCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Import
 */
class SiteCommandTest extends CommandTestCase
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

        $this->command = new SiteCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
        $this->expectWorkflowProcessing();
    }
    
    /**
     * Exercises site:import command with a valid URL
     */
    public function testSiteImportValidURL()
    {
        $url = 'a-valid-url';

        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('import')
            ->with($this->equalTo($url))
            ->willReturn($this->workflow);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Imported site onto Pantheon')
            );

        $out = $this->command->import('dummy-site', $url);
        $this->assertNull($out);
    }

    /**
     * Exercises site:import command with an invalid URL
     */
    public function testSiteImportInvalidURL()
    {
        $url = 'an-invalid-url';

        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('import')
            ->with($this->equalTo($url))
            ->willReturn($this->workflow);
        $this->logger->expects($this->never())
            ->method('log');
        $this->progress_bar->method('cycle')
            ->with()
            ->will($this->throwException(new \Exception('Successfully queued import_site')));

        $this->setExpectedException(TerminusException::class, 'Site import failed');

        $out = $this->command->import('dummy-site', $url);
        $this->assertNull($out);
    }

    /**
     * Exercises site:import command when the workflow throws an exception with a message other than "Successfully queued import_site"
     */
    public function testSiteImportUnspecifiedException()
    {
        $url = 'an-invalid-url';
        $message = 'Any message except the special one';

        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('import')
            ->with($this->equalTo($url))
            ->willReturn($this->workflow);
        $this->logger->expects($this->never())
            ->method('log');
        $this->progress_bar->method('cycle')
            ->with()
            ->will($this->throwException(new \Exception($message)));

        $this->setExpectedException(\Exception::class, $message);

        $out = $this->command->import('dummy-site', $url);
        $this->assertNull($out);
    }
}
