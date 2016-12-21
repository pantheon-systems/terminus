<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use League\Container\Container;
use Pantheon\Terminus\Commands\Env\ViewCommand;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Class ViewCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\ViewCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class ViewCommandTest extends EnvCommandTest
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->expects($this->any())
            ->method('domain')
            ->willReturn('dev-my-site.example.com');

        $this->command = new ViewCommand();
        $this->command->setSites($this->sites);
        $this->command->setContainer($this->container);
    }

    /**
     * Tests the env:view command
     */
    public function testView()
    {
        $this->container->expects($this->never())
            ->method('get');

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
        $this->container->expects($this->never())
            ->method('get');

        $url = $this->command->view('my-site.dev', ['print' => true]);
        $this->assertEquals('http://user:pass@dev-my-site.example.com/', $url);
    }

    /**
     * Tests the env:view command when it opens in a browser window
     */
    public function testViewOpen()
    {
        $expected_url = 'http://dev-my-site.example.com/';

        $local_machine_helper = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->willReturn($local_machine_helper);
        $local_machine_helper->expects($this->once())
            ->method('openUrl')
            ->with($this->equalTo($expected_url));

        $out = $this->command->view('my-site.dev');
        $this->assertNull($out);
    }
}
