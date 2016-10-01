<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Commands\Multidev\CreateCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;

/**
 * Testing class for Pantheon\Terminus\Commands\Multidev\CreateCommand
 */
class CreateCommandTest extends CommandTestCase
{
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new CreateCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->workflow = $this->getMockBuilder(Workflow::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->site->environments->method('create')->willReturn($this->workflow);
    }

    /**
     * Tests the multidev:create command
     */
    public function testMultidevCreate()
    {
        $multidev_name = 'multipass';
        $this->environment->id = 'dev';

        $this->workflow->method('getMessage')->willReturn("Created Multidev environment \"$multidev_name\"");
        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->equalTo('notice'), "Created Multidev environment \"$multidev_name\"");
        $this->workflow->expects($this->once())
            ->method('wait');
        $this->workflow->method('isSuccessful')->willReturn(true);

        $out = $this->command->createMultidev($multidev_name, $this->environment);
        $this->assertNull($out);
    }

    /**
     * Tests to ensure the multidev:create throws an error when the environment-creation operation errs
     *
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage The environment "multipass" already exists.
     */
    public function testMultidevCreateFailure()
    {
        $multidev_name = 'multipass';
        $this->environment->id = 'dev';

        $this->workflow->method('getMessage')->willReturn("The environment \"$multidev_name\" already exists.");
        $this->workflow->expects($this->once())->method('wait');
        $this->workflow->method('isSuccessful')->willReturn(false);

        $out = $this->command->createMultidev($multidev_name, $this->environment);
        $this->assertNull($out);
    }
}
