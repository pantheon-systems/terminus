<?php


namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\WipeCommand;
use Pantheon\Terminus\Models\Workflow;

class WipeCommandTest extends EnvCommandTest
{
    public function testWipeEnv()
    {
        $this->workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->site->expects($this->once())
            ->method('get')
            ->with('name')
            ->willReturn('mysite');

        $this->environment->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('dev');

        $this->environment->expects($this->once())
            ->method('wipe')
            ->willReturn($this->workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Wiping the "{env}" environment of "{site_id}"'),
                $this->equalTo(['site_id' => 'mysite', 'env' => 'dev'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command = new WipeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->wipeEnv('mysite.dev');
    }
}
