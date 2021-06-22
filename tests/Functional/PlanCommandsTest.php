<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class PlanCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PlanCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Plan\InfoCommand
     * @covers \Pantheon\Terminus\Commands\Plan\ListCommand
     * @covers \Pantheon\Terminus\Commands\Plan\SetCommand
     *
     * @group branch
     * @gropu long
     */
    public function testConnection()
    {
        $this->fail("To Be Written");
    }
}
