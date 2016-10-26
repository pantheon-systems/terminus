<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\ViewCommand;

class ViewCommandTest extends EnvCommandTest
{
    public function testView()
    {
        $this->env->expects($this->any())
            ->method('domain')
            ->willReturn('dev-my-site.example.com');

        $command = new ViewCommand();
        $command->setSites($this->sites);
        $url = $command->view('my-site.dev', ['print' => true]);
        $this->assertEquals('http://dev-my-site.example.com/', $url);
    }

    public function testViewLocked()
    {
        $this->env->expects($this->any())
            ->method('domain')
            ->willReturn('dev-my-site.example.com');
        $this->env->expects($this->any())
            ->method('get')
            ->with('lock')
            ->willReturn(
                (object)[
                    'locked' => true,
                    'username' => 'user',
                    'password' => 'pass',
                ]
            );

        $command = new ViewCommand();
        $command->setSites($this->sites);
        $url = $command->view('my-site.dev', ['print' => true]);
        $this->assertEquals('http://user:pass@dev-my-site.example.com/', $url);
    }
}
