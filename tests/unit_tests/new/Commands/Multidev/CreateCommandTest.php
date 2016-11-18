<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\CreateCommand;

/**
 * Class CreateCommandTest
 * Testing class for Pantheon\Terminus\Commands\Multidev\CreateCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Multidev
 */
class CreateCommandTest extends MultidevCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new CreateCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->environments->method('create')->willReturn($this->workflow);
    }

    /**
     * Tests the multidev:create command
     */
    public function testCreate()
    {
        $multidev_name = 'multipass';
        $this->environment->id = 'dev';

        $this->workflow->method('isSuccessful')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn("Created Multidev environment \"$multidev_name\"");
        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->equalTo('notice'), "Created Multidev environment \"$multidev_name\"");

        $out = $this->command->create($multidev_name, $this->environment);
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:create throws an error when the environment-creation operation errs
     *
     * @expectedExceptionMessage The environment "multipass" already exists.
     */
    public function testCreateFailure()
    {
        $multidev_name = 'multipass';
        $this->environment->id = 'dev';

        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn("The environment \"$multidev_name\" already exists.");

        $out = $this->command->create($multidev_name, $this->environment);
        $this->assertNull($out);
    }
}
