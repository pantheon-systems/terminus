<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

class OrgCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use SiteBaseSetupTrait;
    use LoginHelperTrait;


    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Org\ListCommand
     * @group org
     * @group short
     */
    public function testOrgListCommand()
    {
        $orgList = $this->terminusJsonResponse("org:list");
        $this->assertIsArray(
            $orgList,
            "Response from org list should be an array of orgs"
        );
        $org = array_shift($orgList);

        $this->assertIsArray(
            $org,
            "row from org list array of orgs should be an org item"
        );
        $this->assertArrayHasKey('id', $org, "Orgs from org list should have an id property");
        $this->assertArrayHasKey('name', $org, "Orgs from org list should have a name property");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Org\People\ListCommand
     * @group org
     * @group short
     */
    public function testOrgPeopleListCommand()
    {
        $people = $this->terminusJsonResponse("org:people:list " . $this->org);
        $this->assertIsArray(
            $people,
            "Response from org list should be an array of orgs"
        );
        $person = array_shift($people);

        $this->assertIsArray(
            $person,
            "row from org list array of orgs should be an org item"
        );
        $this->assertArrayHasKey(
            'id',
            $person,
            "Person from Org People List should have an ID property"
        );
        $this->assertArrayHasKey(
            'email',
            $person,
            "Person from Org people List should have email address"
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Org\Site\ListCommand
     * @group org
     * @group short
     */
    public function testOrgSiteListCommand()
    {
        $orgSites = $this->terminusJsonResponse("org:site:list " . $this->org);
        $this->assertIsArray(
            $orgSites,
            "Response from org list should be an array of orgs"
        );
        $site = array_shift($orgSites);

        $this->assertIsArray(
            $site,
            "row from org list array of orgs should be an org item"
        );
        $this->assertArrayHasKey('id', $site, "Sites from org list should have an id property");
        $this->assertArrayHasKey('name', $site, "Sites from org list should have a name property");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Org\Upstream\ListCommand
     * @group org
     * @group short
     */
    public function testOrgUpstreamList()
    {
        $upstreams = $this->terminusJsonResponse("org:upstream:list " . $this->org);
        $this->assertIsArray(
            $upstreams,
            "Response from org list should be an array of orgs"
        );
        $upstream = array_shift($upstreams);

        $this->assertIsArray(
            $upstream,
            "row from org list array of orgs should be an org item"
        );
        $this->assertArrayHasKey(
            'id',
            $upstream,
            "Orgs from org list should have an id property"
        );
        $this->assertArrayHasKey(
            'label',
            $upstream,
            "Orgs from org list should have a name property"
        );
        $this->assertArrayHasKey(
            'machine_name',
            $upstream,
            "Orgs from org list should have a name property"
        );
    }
}
