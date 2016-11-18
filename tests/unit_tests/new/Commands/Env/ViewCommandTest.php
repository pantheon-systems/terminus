<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\ViewCommand;

/**
 * Class ViewCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\ViewCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class ViewCommandTest extends EnvCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->environment->expects($this->any())
            ->method('domain')
            ->willReturn('dev-my-site.example.com');

        $this->command = new ViewCommand();
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the env:view command
     */
    public function testView()
    {
        $url = $this->command->view('my-site.dev', ['print' => true]);
        $this->assertEquals('http://dev-my-site.example.com/', $url);
    }

    /**
     * Tests the env:view command when the environment is locked
     */
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
