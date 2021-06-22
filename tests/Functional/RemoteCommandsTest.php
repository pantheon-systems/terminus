<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class RemoteCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class RemoteCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Remote\DrushCommand
     * @covers \Pantheon\Terminus\Commands\Remote\WPCommand
     *
     * @group remote
     * @gropu long
     */
    public function testConnection()
    {
        $this->fail("To Be Written");
    }
}
