<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\DeleteCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Symfony\Component\Console\Input\Input;

/**
 * Class DeleteCommandTest
 * Testing class for Pantheon\Terminus\Commands\Multidev\DeleteCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Multidev
 */
class DeleteCommandTest extends MultidevCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->environment->method('delete')->willReturn($this->workflow);

        $this->command = new DeleteCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->command->setInput($this->input);
    }

    /**
     * Tests the multidev:create command
     */
    public function testMultidevDelete()
    {
        $this->environment->id = 'multipass';

        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('delete')
            ->with();
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Deleted the multidev environment {env}."),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->deleteMultidev("site.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests the multidev:create command when declining the confirmation
     *
     * @todo Remove this when removing TerminusCommand::confirm()
     */
    public function testMultidevDeleteConfirmationDecline()
    {
        $this->environment->id = 'multipass';

        $this->expectConfirmation(false);
        $this->environment->expects($this->never())
            ->method('delete');
        $this->workflow->expects($this->never())
            ->method('checkProgress');
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->deleteMultidev("site.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:delete to ensure it passes the 'delete-branch' option successfully
     */
    public function testMultidevDeleteWithBranch()
    {
        $this->environment->id = 'multipass';

        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(['delete_branch' => true,]))
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Deleted the multidev environment {env}."),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->deleteMultidev("site.{$this->environment->id}", ['delete-branch' => true,]);
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:create throws an error when the environment-creation operation fails
     *
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage The {env} environment could not be deleted.
     */
    public function testMultidevDeleteFailure()
    {
        $message = 'The {env} environment could not be deleted.';
        $this->environment->id = 'env id';
        $expected_message = "The {$this->environment->id} environment could not be deleted.";

        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(['delete_branch' => false,]))
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->will($this->throwException(new TerminusException($message, ['env' => $this->environment->id,])));

        $this->setExpectedException(TerminusException::class, $expected_message);

        $out = $this->command->deleteMultidev("site.{$this->environment->id}");
        $this->assertNull($out);
    }
}
