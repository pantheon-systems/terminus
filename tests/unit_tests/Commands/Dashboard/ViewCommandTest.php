<?php

namespace Pantheon\Terminus\UnitTests\Commands\Dashboard;

use League\Container\Container;
use Pantheon\Terminus\Commands\Dashboard\ViewCommand;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ViewCommandTest
 * Testing class for Pantheon\Terminus\Commands\Dashboard\ViewCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Dashboard
 */
class ViewCommandTest extends CommandTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Session
     */
    protected $session;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ViewCommand();
        $this->command->setSites($this->sites);
        $this->command->setContainer($this->container);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the dashboard:view command when opening to the user view
     */
    public function testViewUserDashboard()
    {
        $dashboard_url = 'https://dashboard.pantheon.io/users/some_uuid';
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->never())->method('get');
        $this->site->expects($this->never())->method('dashboardUrl');
        $this->environment->expects($this->never())->method('dashboardUrl');
        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        $user->expects($this->once())
            ->method('dashboardUrl')
            ->with()
            ->willReturn($dashboard_url);

        $url = $this->command->view(null, ['print' => true,]);
        $this->assertEquals($dashboard_url, $url);
    }

    /**
     * Tests the dashboard:view command when opening to a site view
     */
    public function testViewSiteDashboardOpen()
    {
        $dashboard_url = 'https://dashboard.pantheon.io/sites/some_uuid';
        $local_machine_helper = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->expects($this->never())->method('dashboardUrl');
        $this->session->expects($this->never())->method('getUser');
        $this->site->expects($this->once())
            ->method('dashboardUrl')
            ->with()
            ->willReturn($dashboard_url);
        $this->container->expects($this->once())
            ->method('get')
            ->willReturn($local_machine_helper);
        $local_machine_helper->expects($this->once())
            ->method('openUrl')
            ->with($this->equalTo($dashboard_url));

        $out = $this->command->view('my-site');
        $this->assertNull($out);
    }

    /**
     * Tests the dashboard:view command when opening to a site view
     */
    public function testViewSiteDashboardPrint()
    {
        $dashboard_url = 'https://dashboard.pantheon.io/sites/some_uuid';

        $this->container->expects($this->never())->method('get');
        $this->environment->expects($this->never())->method('dashboardUrl');
        $this->session->expects($this->never())->method('getUser');
        $this->site->expects($this->once())
            ->method('dashboardUrl')
            ->with()
            ->willReturn($dashboard_url);

        $url = $this->command->view('my-site', ['print' => true,]);
        $this->assertEquals($dashboard_url, $url);
    }

    /**
     * Tests the dashboard:view command when opening to an environment view
     */
    public function testViewEnvDashboard()
    {
        $dashboard_url = 'https://dashboard.pantheon.io/sites/some_uuid/dev';

        $this->container->expects($this->never())->method('get');
        $this->site->expects($this->never())->method('dashboardUrl');
        $this->session->expects($this->never())->method('getUser');
        $this->environment->expects($this->once())
            ->method('dashboardUrl')
            ->with()
            ->willReturn($dashboard_url);

        $url = $this->command->view('my-site.dev', ['print' => true,]);
        $this->assertEquals($dashboard_url, $url);
    }
}
