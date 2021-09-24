<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DashboardCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class DashboardCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Dashboard\ViewCommand
     *
     * @group dashboard
     * @group short
     */
    public function testDashboardViewCommand()
    {
        $dashboardUrl = $this->terminus(sprintf('dashboard %s --print', $this->getSiteEnv()));
        $this->assertIsString($dashboardUrl);
        $this->assertNotEmpty($dashboardUrl);

        $dashboardUrl = $this->terminus(sprintf('dashboard %s --print', $this->getSiteName()));
        $this->assertIsString($dashboardUrl);
        $this->assertNotEmpty($dashboardUrl);

        $dashboardUrl = $this->terminus('dashboard --print');
        $this->assertIsString($dashboardUrl);
        $this->assertNotEmpty($dashboardUrl);
    }
}
