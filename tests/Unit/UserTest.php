<?php

namespace Pantheon\Terminus\Tests\Unit;

use Consolidation\Config\Config;
use Pantheon\Terminus\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testDashboardUrlPointsAtDashboardRoot()
    {
        $user = new User((object)['id' => 'user-uuid-123']);
        $user->setConfig(new Config([
            'dashboard_protocol' => 'https',
            'dashboard_host' => 'dashboard.pantheon.io',
        ]));

        $this->assertSame('https://dashboard.pantheon.io/', $user->dashboardUrl());
    }

    public function testDashboardUrlUsesConfiguredProtocolAndHost()
    {
        $user = new User((object)['id' => 'user-uuid-123']);
        $user->setConfig(new Config([
            'dashboard_protocol' => 'http',
            'dashboard_host' => 'dashboard.sandbox.example',
        ]));

        $this->assertSame('http://dashboard.sandbox.example/', $user->dashboardUrl());
    }
}
