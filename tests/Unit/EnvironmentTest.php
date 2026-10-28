<?php

namespace Pantheon\Terminus\Tests\Unit;

use Consolidation\Config\Config;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    private function createEnvironment(string $envId): Environment
    {
        $site = new Site((object)['id' => 'site-uuid-123']);
        $site->setConfig(new Config([
            'dashboard_protocol' => 'https',
            'dashboard_host' => 'dashboard.pantheon.io',
        ]));

        return new Environment((object)['id' => $envId], ['site' => $site]);
    }

    public function testDashboardUrlPutsEnvironmentInThePath()
    {
        $this->assertSame(
            'https://dashboard.pantheon.io/site-redirect/site-uuid-123/environment/live/',
            $this->createEnvironment('live')->dashboardUrl()
        );
    }

    public function testDashboardUrlPassesMultidevNameThrough()
    {
        $this->assertSame(
            'https://dashboard.pantheon.io/site-redirect/site-uuid-123/environment/my-feature/',
            $this->createEnvironment('my-feature')->dashboardUrl()
        );
    }
}
