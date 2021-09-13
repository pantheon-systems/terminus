<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class DomainCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Domain\AddCommand
     * @covers \Pantheon\Terminus\Commands\Domain\DNSCommand
     * @covers \Pantheon\Terminus\Commands\Domain\ListCommand
     * @covers \Pantheon\Terminus\Commands\Domain\LookupCommand
     * @covers \Pantheon\Terminus\Commands\Domain\RemoveCommand
     * @covers \Pantheon\Terminus\Commands\Domain\Primary\AddCommand
     * @covers \Pantheon\Terminus\Commands\Domain\Primary\RemoveCommand
     *
     * @group domain
     * @group long
     */
    public function testDomainAddListLookupRemove()
    {
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $this->getSiteEnv()));
        $this->assertIsArray($domainList);
        $this->assertNotEmpty($domainList);

        $testDomain = uniqid('test-') . '.test';
        $this->terminus(sprintf('domain:add %s %s', $this->getSiteEnv(), $testDomain));
        sleep(10);
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $this->getSiteEnv()));
        $domains = array_column($domainList, 'id');
        $this->assertContains($testDomain, $domains, 'Domain list should contain added domain');

        // @fixme CMS-238
//        $lookUpResult = $this->terminusJsonResponse(sprintf('domain:lookup %s', $newDomain));
//        $this->assertEquals([], $lookUpResult);

        $this->terminus(sprintf('domain:primary:add %s %s', $this->getSiteEnv(), $testDomain));
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $this->getSiteEnv()));
        $primaryDomains = array_combine(array_column($domainList, 'id'), array_column($domainList, 'primary'));
        $this->assertArrayHasKey($testDomain, $primaryDomains, 'Domain list should contain the test domain');
        $this->assertEquals('1', $primaryDomains[$testDomain], 'The test domain should be primary');

        $this->terminus(sprintf('domain:primary:remove %s', $this->getSiteEnv()));
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $this->getSiteEnv()));
        $primaryDomains = array_combine(array_column($domainList, 'id'), array_column($domainList, 'primary'));
        $this->assertArrayHasKey($testDomain, $primaryDomains, 'Domain list should contain the test domain');
        $this->assertNotEquals('1', $primaryDomains[$testDomain], 'The test domain should not be primary anymore');

        $this->terminus(sprintf('domain:remove %s %s', $this->getSiteEnv(), $testDomain));
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $this->getSiteEnv()));
        $domains = array_column($domainList, 'id');
        $this->assertFalse(array_search($testDomain, $domains), 'Domain list should no longer contain the test domain');
    }
}
