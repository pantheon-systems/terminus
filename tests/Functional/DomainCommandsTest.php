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
        // Running tests against the "test" environment instead of a multidev one since "domain:lookup" command does not
        // search domains across multidev environments.
        $siteEnv = sprintf('%s.test', $this->getSiteName());
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $siteEnv));
        $this->assertIsArray($domainList);
        $this->assertNotEmpty($domainList);

        $testDomain = uniqid('test-') . '.test';
        $this->terminus(sprintf('domain:add %s %s', $siteEnv, $testDomain));
        sleep(10);
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $siteEnv));
        $domains = array_column($domainList, 'id');
        $this->assertContains($testDomain, $domains, 'Domain list should contain added domain');

        $lookUpResult = $this->terminusJsonResponse(sprintf('domain:lookup %s', $testDomain));
        $this->assertIsArray($lookUpResult);
        $this->assertNotEmpty($lookUpResult);
        $this->assertArrayHasKey('site_id', $lookUpResult);
        $this->assertNotEmpty($lookUpResult['site_id']);
        $this->assertArrayHasKey('site_name', $lookUpResult);
        $this->assertNotEmpty($lookUpResult['site_name']);
        $this->assertEquals($this->getSiteName(), $lookUpResult['site_name']);
        $this->assertArrayHasKey('env_id', $lookUpResult);
        $this->assertEquals('test', $lookUpResult['env_id']);

        $this->terminus(sprintf('domain:primary:add %s %s', $siteEnv, $testDomain));
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $siteEnv));
        $primaryDomains = array_combine(array_column($domainList, 'id'), array_column($domainList, 'primary'));
        $this->assertArrayHasKey($testDomain, $primaryDomains, 'Domain list should contain the test domain');
        $this->assertEquals('1', $primaryDomains[$testDomain], 'The test domain should be primary');

        $this->terminus(sprintf('domain:primary:remove %s', $siteEnv));
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $siteEnv));
        $primaryDomains = array_combine(array_column($domainList, 'id'), array_column($domainList, 'primary'));
        $this->assertArrayHasKey($testDomain, $primaryDomains, 'Domain list should contain the test domain');
        $this->assertNotEquals('1', $primaryDomains[$testDomain], 'The test domain should not be primary anymore');

        $this->terminus(sprintf('domain:remove %s %s', $siteEnv, $testDomain));
        $domainList = $this->terminusJsonResponse(sprintf('domain:list %s', $siteEnv));
        $domains = array_column($domainList, 'id');
        $this->assertFalse(array_search($testDomain, $domains), 'Domain list should no longer contain the test domain');
    }
}
