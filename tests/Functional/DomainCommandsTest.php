<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class DomainCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Domain\AddCommand
     * @covers \Pantheon\Terminus\Commands\Domain\DNSCommand
     * @covers \Pantheon\Terminus\Commands\Domain\ListCommand
     * @covers \Pantheon\Terminus\Commands\Domain\LookupCommand
     * @covers \Pantheon\Terminus\Commands\Domain\RemoveCommand
     *
     * @group domain
     * @gropu long
     */
    public function testAddListLookupRemove()
    {
        $sitename = getenv('TERMINUS_SITE');
        $this->fail("To Be Written.");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Domain\Primary\AddCommand
     * @covers \Pantheon\Terminus\Commands\Domain\Primary\RemoveCommand
     *
     * @group domain
     * @gropu long
     */
    public function testPrimaryAddRemove()
    {
        $sitename = getenv('TERMINUS_SITE');
        $this->fail("To Be Written.");
    }
}
