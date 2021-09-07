<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DashboardCommandsTest
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
    public function testDashboardUrl()
    {
        $response = $this->terminus("dashboard --print", null);
        $this->assertNotNull($response);
        $this->assertIsString($response);
        $this->assertGreaterThan(0, strlen($response));
        $siteName = $this->getSiteName();
        $env = getenv('TERMINUS_ENV');
        $response = $this->terminus("dashboard {$siteName}.{$env} --print", null);
        $this->assertNotNull($response);
        $this->assertIsString($response);
        $this->assertGreaterThan(0, strlen($response));
    }
}
