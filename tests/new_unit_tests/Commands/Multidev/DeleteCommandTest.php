<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\DeleteCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Multidev\DeleteCommand
 */
class DeleteCommandTest extends MultidevCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new DeleteCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->environment->method('delete')->willReturn($this->workflow);
    }

    /**
     * Tests the multidev:create command
     */
    public function testMultidevDelete()
    {
        $this->environment->id = 'multipass';

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Deleted the multidev environment {env}."),
                $this->equalTo(['env' => $this->environment->id,])
            );
        $this->workflow->expects($this->once())
            ->method('wait');
        $this->workflow->method('isSuccessful')->willReturn(true);
        $this->environment->expects($this->once())
            ->method('delete')
            ->with();

        $out = $this->command->deleteMultidev("site.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:delete to ensure it passes the 'delete-branch' option successfully
     */
    public function testMultidevDeleteWithBranch()
    {
        $this->environment->id = 'multipass';

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Deleted the multidev environment {env}."),
                $this->equalTo(['env' => $this->environment->id,])
            );
        $this->workflow->expects($this->once())
            ->method('wait');
        $this->environment->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(['delete_branch' => true,]));
        $this->workflow->method('isSuccessful')->willReturn(true);

        $out = $this->command->deleteMultidev("site.{$this->environment->id}", ['delete-branch' => true,]);
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:create throws an error when the environment-creation operation fails
     *
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage The {env} environment could not be deleted.
     */
    public function testMultidevDeleteFailure()
    {
        $this->workflow->method('getMessage')->willReturn("The {env} environment could not be deleted.");
        $this->workflow->expects($this->once())
            ->method('wait');
        $this->environment->expects($this->once())
            ->method('delete')
            ->with();
        $this->workflow->method('isSuccessful')->willReturn(false);

        $out = $this->command->deleteMultidev('site.multipass');
        $this->assertNull($out);
    }
}
