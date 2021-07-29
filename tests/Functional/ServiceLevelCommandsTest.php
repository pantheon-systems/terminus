<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ServiceLevelCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ServiceLevelCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\ServiceLevel\SetCommand
     *
     * @group service-level
     * @gropu long
     */
    public function testConnection()
    {
        $this->fail("To Be Written");
    }
}
