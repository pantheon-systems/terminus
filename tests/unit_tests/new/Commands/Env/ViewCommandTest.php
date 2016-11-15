<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\ViewCommand;

class ViewCommandTest extends EnvCommandTest
{

    public function setUp()
    {
        parent::setUp();

        $this->environment->expects($this->any())
            ->method('domain')
            ->willReturn('dev-my-site.example.com');

        $this->command = new ViewCommand();
        $this->command->setSites($this->sites);
    }

    public function testView()
    {
        $url = $this->command->view('my-site.dev', ['print' => true]);
        $this->assertEquals('http://dev-my-site.example.com/', $url);
    }

    public function testViewLocked()
    {
        $this->environment->expects($this->any())
            ->method('get')
            ->with('lock')
            ->willReturn(
                (object)[
                    'locked' => true,
                    'username' => 'user',
                    'password' => 'pass',
                ]
            );

        $url = $this->command->view('my-site.dev', ['print' => true]);
        $this->assertEquals('http://user:pass@dev-my-site.example.com/', $url);
    }
}
