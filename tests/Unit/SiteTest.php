<?php

namespace Pantheon\Terminus\Tests\Unit;

use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Models\Site;
use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase
{
    public function testDashboardUrlUsesSiteRedirectRoute()
    {
        $site = new Site((object)['id' => 'site-uuid-123']);
        $site->setConfig(new TerminusConfig([
            'dashboard_protocol' => 'https',
            'dashboard_host' => 'dashboard.pantheon.io',
        ]));

        $this->assertSame(
            'https://dashboard.pantheon.io/site-redirect/site-uuid-123/',
            $site->dashboardUrl()
        );
    }

    public function testDashboardUrlUsesConfiguredProtocolAndHost()
    {
        $site = new Site((object)['id' => 'site-uuid-123']);
        $site->setConfig(new TerminusConfig([
            'dashboard_protocol' => 'http',
            'dashboard_host' => 'dashboard.sandbox.example',
        ]));

        $this->assertSame(
            'http://dashboard.sandbox.example/site-redirect/site-uuid-123/',
            $site->dashboardUrl()
        );
    }
}
