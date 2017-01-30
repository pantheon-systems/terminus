<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\WakeCommand;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class WakeCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\WakeCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class WakeCommandTest extends EnvCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new WakeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the env:wake command
     */
    public function testWakeEnv()
    {
        $this->environment->expects($this->once())
            ->method('wake')
            ->with()
            ->willReturn(['success' => true, 'target' => 'dev', 'styx' => 'yep!',]);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('OK >> {target} responded'),
                $this->equalTo(['success' => true, 'target' => 'dev', 'styx' => 'yep!',])
            );

        $out = $this->command->wake('mysite.dev');
        $this->assertNull($out);
    }

    /**
     * Tests the env:wake command when the operation fails to reach the environment
     */
    public function testWakeFailUnreachable()
    {
        $this->environment->expects($this->once())
            ->method('wake')
            ->with()
            ->willReturn(['success' => false, 'target' => 'dev',]);

        $this->setExpectedException(TerminusException::class, 'Could not reach dev');

        $out = $this->command->wake('mysite.dev');
        $this->assertNull($out);
    }

    /**
     * Tests the env:wake command when the operation fails because Styx data is missing
     */
    public function testWakeFail()
    {
        $this->environment->expects($this->once())
            ->method('wake')
            ->with()
            ->willReturn(['success' => true, 'target' => 'dev',]);

        $this->setExpectedException(TerminusException::class, 'Pantheon headers missing, which is not quite right.');

        $out = $this->command->wake('mysite.dev');
        $this->assertNull($out);
    }
}
