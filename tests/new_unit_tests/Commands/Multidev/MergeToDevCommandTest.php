<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\MergeToDevCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Multidev\MergeToDevCommand
 */
class MergeToDevCommandTest extends MultidevCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new MergeToDevCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->environment->method('mergeToDev')->willReturn($this->workflow);
    }

    /**
     * Tests the multidev:merge-to-dev command
     */
    public function testMultidevDelete()
    {
        $this->environment->id = 'multipass';

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Merged the {env} environment into dev."),
                $this->equalTo(['env' => $this->environment->id,])
            );
        $this->workflow->expects($this->once())
          ->method('wait');
        $this->environment->expects($this->once())
          ->method('mergeToDev')
          ->with($this->equalTo(['from_environment' => $this->environment->id, 'updatedb' => false,]));
        $this->workflow->method('isSuccessful')->willReturn(true);

        $out = $this->command->mergeToDev("site.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:merge-to-dev to ensure it passes the 'updatedb' option successfully
     */
    public function testMultidevDeleteWithBranch()
    {
        $this->environment->id = 'multipass';

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Merged the {env} environment into dev."),
                $this->equalTo(['env' => $this->environment->id,])
            );
        $this->workflow->expects($this->once())
            ->method('wait');
        $this->environment->expects($this->once())
            ->method('mergeToDev')
            ->with($this->equalTo(['from_environment' => $this->environment->id, 'updatedb' => true,]));
        $this->workflow->method('isSuccessful')->willReturn(true);

        $out = $this->command->mergeToDev("site.{$this->environment->id}", ['updatedb' => true,]);
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:merge-to-dev throws an error when the environment-creation operation fails
     *
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage The {env} environment could not be merged into dev.
     */
    public function testMultidevDeleteFailure()
    {
        $this->environment->id = 'multipass';

        $this->workflow->method('getMessage')->willReturn("The {env} environment could not be merged into dev.");
        $this->workflow->expects($this->once())
            ->method('wait');
        $this->environment->expects($this->once())
            ->method('mergeToDev')
            ->with($this->equalTo(['from_environment' => $this->environment->id, 'updatedb' => false,]));
        $this->workflow->method('isSuccessful')->willReturn(false);

        $out = $this->command->mergeToDev("site.{$this->environment->id}");
        $this->assertNull($out);
    }
}
