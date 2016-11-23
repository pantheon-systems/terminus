<?php


namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\WakeCommand;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;

class WakeCommandTest extends EnvCommandTest
{
    public function testWakeEnv()
    {
        $this->environment->expects($this->once())
            ->method('wake')
            ->willReturn(['success' => true, 'target' => 'dev', 'time' => 1, 'styx' => 'yep!']);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('OK >> {target} responded in {time}'),
                $this->equalTo(['success' => true, 'target' => 'dev', 'time' => 1, 'styx' => 'yep!'])
            );

        $this->command = new WakeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->wakeEnv('mysite.dev');
    }

    public function testWakeFail()
    {
        $this->environment->expects($this->once())
            ->method('wake')
            ->willReturn(['success' => false, 'target' => 'dev']);

        $this->setExpectedException(TerminusException::class, 'Could not reach dev');

        $this->command = new WakeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->wakeEnv('mysite.dev');
    }

    public function testWakeNoStyx()
    {
        $this->environment->expects($this->once())
            ->method('wake')
            ->willReturn(['success' => true, 'target' => 'dev']);

        $this->setExpectedException(TerminusException::class, 'Pantheon headers missing, which is not quite right.');

        $this->command = new WakeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->wakeEnv('mysite.dev');
    }
}
