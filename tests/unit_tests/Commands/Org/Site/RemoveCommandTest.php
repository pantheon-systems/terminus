<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Site;

use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Commands\Org\Site\RemoveCommand;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\Site\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\Site
 */
class RemoveCommandTest extends OrgSiteCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @var OrganizationSiteMembership
     */
    protected $org_site_membership;
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

        $this->site->id = 'site_id';

        $this->org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_site_membership->method('getSite')->willReturn($this->site);
        $this->org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_site_memberships->method('get')
            ->with($this->site->id)
            ->willReturn($this->org_site_membership);
        $this->organization->method('getSiteMemberships')
            ->with()
            ->willReturn($this->org_site_memberships);

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the org:site:remove command
     */
    public function testRemove()
    {
        $org_name = 'organization-name';
        $site_name = 'Site Name';

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_site_membership->expects($this->once())
            ->method('delete')
            ->with()
            ->willReturn($this->workflow);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($org_name);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{site} has been removed from the {org} organization.'),
                $this->equalTo(['site' => $site_name, 'org' => $org_name,])
            );

        $out = $this->command->remove($this->organization->id, $this->site->id);
        $this->assertNull($out);
    }
}
