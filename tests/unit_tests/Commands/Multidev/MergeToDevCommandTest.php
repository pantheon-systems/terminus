<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\MergeToDevCommand;

/**
 * Class MergeToDevCommandTest
 * Testing class for Pantheon\Terminus\Commands\Multidev\MergeToDevCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Multidev
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
    public function testMergeToDev()
    {
        $this->environment->id = 'multipass';

        $this->environment->expects($this->once())
            ->method('mergeToDev')
            ->with($this->equalTo(['from_environment' => $this->environment->id, 'updatedb' => false,]));
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->method('isSuccessful')->willReturn(true);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Merged the {env} environment into dev."),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->mergeToDev("site.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:merge-to-dev to ensure it passes the 'updatedb' option successfully
     */
    public function testMergeToDevWithUpdateDB()
    {
        $this->environment->id = 'multipass';

        $this->environment->expects($this->once())
            ->method('mergeToDev')
            ->with($this->equalTo(['from_environment' => $this->environment->id, 'updatedb' => true,]));
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->method('isSuccessful')->willReturn(true);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("Merged the {env} environment into dev."),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->mergeToDev("site.{$this->environment->id}", ['updatedb' => true,]);
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:merge-to-dev throws an error when the environment-creation operation fails
     *
     * @expectedExceptionMessage The {env} environment could not be merged into dev.
     */
    public function testMergeToDevFailure()
    {
        $this->environment->id = 'multipass';

        $this->environment->expects($this->once())
            ->method('mergeToDev')
            ->with($this->equalTo(['from_environment' => $this->environment->id, 'updatedb' => false,]));
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->method('isSuccessful')->willReturn(false);
        $this->workflow->method('getMessage')->willReturn("The {env} environment could not be merged into dev.");

        $out = $this->command->mergeToDev("site.{$this->environment->id}");
        $this->assertNull($out);
    }
}
