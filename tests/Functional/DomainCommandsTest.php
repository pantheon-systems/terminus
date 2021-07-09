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
        $newDomain = uniqid("test-") . ".test";

        $results = $this->terminusJsonResponse("domain:list {$sitename}.live");
        $this->assertIsArray($results, "Returned values from domain list should be array");
        $this->assertGreaterThan(
            0,
            count($results),
            "Count of domains should be greater than 0"
        );
        $this->terminus("domain:add {$sitename}.live {$newDomain}", null);
        sleep(10);
        $results2 = $this->terminusJsonResponse("domain:list {$sitename}.live");
        $this->assertNotEquals(
            count($results),
            count($results2),
            "response should have a new domain in list"
        );
        $lookedUp = $this->terminusJsonResponse("domain:lookup {$newDomain}", null);
        $this->terminus("domain:remove {$sitename}.live {$newDomain}");
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
