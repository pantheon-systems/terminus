<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class LockCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class LockCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Lock\DisableCommand
     * @covers \Pantheon\Terminus\Commands\Lock\EnableCommand
     * @covers \Pantheon\Terminus\Commands\Lock\InfoCommand
     *
     * @group branch
     * @gropu long
     */
    public function testBranchList()
    {
        $this->fail("To Be Written");
    }
}
