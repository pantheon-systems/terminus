<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class D9ifyCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class D9ifyCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\D9ify\ProcessCommand
     *
     * @group d9ify
     * @gropu long
     */
    public function testBranchList()
    {
        $this->fail("To Be written");
    }
}
