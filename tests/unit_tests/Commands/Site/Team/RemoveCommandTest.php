<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pantheon\Terminus\Commands\Site\Team\RemoveCommand;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Team\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Team
 */
class RemoveCommandTest extends TeamCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @var string
     */
    protected $message;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->message = 'message';

        $this->user_membership->expects($this->once())
            ->method('delete')
            ->willReturn($this->workflow);

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the site:team:remove command when it succeeds without issue
     */
    public function testRemoveCommand()
    {
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($this->message);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($this->message)
            );

        $out = $this->command->remove('mysite', 'test@example.com');
        $this->assertNull($out);
    }

    /**
     * Tests the site:team:remove command when the workflow throws an error because
     * the user is no longer a team member permitted to access the site's workflows
     */
    public function testRemoveCommandRemovingSelf()
    {
        $this->progress_bar->method('cycle')
            ->with()
            ->will($this->throwException(new \Exception($this->message, 404)));
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Removed your user from site team')
            );

        $out = $this->command->remove('mysite', 'test@example.com');
        $this->assertNull($out);
    }

    /**
     * Tests the site:team:remove command when the workflow throws an error but it is not the error
     * which would occur when removing oneself from a team
     */
    public function testRemoveCommandErrs()
    {
        $this->progress_bar->method('cycle')
            ->with()
            ->will($this->throwException(new \Exception($this->message, 403)));
        $this->setExpectedException(\Exception::class, $this->message);

        $out = $this->command->remove('mysite', 'test@example.com');
        $this->assertNull($out);
    }
}
