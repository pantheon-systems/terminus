<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Site\DeleteCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class DeleteCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\DeleteCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class DeleteCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var string
     */
    protected $message;
    /**
     * @var string
     */
    protected $site_name;
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
        $this->message = 'Deleted {site} from Pantheon';

        $this->site_name = 'my-site';
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);

        $this->command = new DeleteCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
    }

    /**
     * Exercises the site:delete command
     */
    public function testDelete()
    {
        $this->expectConfirmation();
        $this->expectWorkflowProcessing();
        $this->site->expects($this->once())
            ->method('delete')
            ->with()
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($this->message);
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo($this->message),
                $this->equalTo(['site' => $this->site_name,])
            );

        $out = $this->command->delete($this->site_name);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:delete command when the workflow ends with a 404 error
     */
    public function testDelete404()
    {
        $this->expectConfirmation();
        $this->expectWorkflowProcessing();
        $this->site->expects($this->once())
            ->method('delete')
            ->with()
            ->willReturn($this->workflow);
        $this->progress_bar->method('cycle')
            ->will($this->throwException(new \Exception($this->message, 404)));
        $this->workflow->expects($this->never())
            ->method('getMessage');
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo($this->message),
                $this->equalTo(['site' => $this->site_name,])
            );

        $out = $this->command->delete($this->site_name);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:delete command when the workflow returns a non-404 error
     */
    public function testDeleteErrs()
    {
        $this->expectConfirmation();
        $this->expectInteractiveInput();
        $this->expectContainerRetrieval();
        $this->site->expects($this->once())
            ->method('delete')
            ->with()
            ->willReturn($this->workflow);
        $this->getProgressBar()->method('cycle')
            ->will($this->throwException(new \Exception($this->message, 403)));
        $this->setExpectedException(\Exception::class, $this->message);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->delete($this->site_name);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:delete command when Site::delete fails to ensure message gets through
     */
    public function testDeleteFailure()
    {
        $exception_message = 'Error message';

        $this->expectConfirmation();
        $this->expectWorkflowProcessing();
        $this->site->expects($this->once())
            ->method('delete')
            ->with()
            ->will($this->throwException(new \Exception($exception_message)));
        $this->logger->expects($this->never())
            ->method('log');

        $this->setExpectedException(\Exception::class, $exception_message);

        $out = $this->command->delete($this->site_name);
        $this->assertNull($out);
    }
}
