<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Site;

use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Commands\Org\Site\AddCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Testing class for Pantheon\Terminus\Commands\Org\Site\AddCommand
 */
class AddCommandTest extends OrgSiteCommandTest
{
    /**
     * @var OrganizationSiteMemberships
     */
    protected $org_site_memberships;
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization->method('getSiteMemberships')
            ->with()
            ->willReturn($this->org_site_memberships);

        $this->command = new AddCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the org:site:add command
     */
    public function testAdd()
    {
        $org_name = 'Organization Name';
        $site_name = 'Site Name';

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_site_memberships->expects($this->once())
            ->method('create')
            ->with($this->site)
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->organization->expects($this->once())
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn((object)['name' => $org_name,]);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{site} has been added to the {org} organization.'),
                $this->equalTo(['site' => $site_name, 'org' => $org_name,])
            );

        $out = $this->command->add($this->organization->id, $this->site->id);
        $this->assertNull($out);
    }
}
