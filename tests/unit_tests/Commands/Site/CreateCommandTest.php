<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class CreateCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\CreateCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class CreateCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var Upstream
     */
    protected $upstream;
    /**
     * @var Upstreams
     */
    protected $upstreams;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var UserOrganizationMembership
     */
    protected $user_org_membership;
    /**
     * @var UserOrganizationMemberships
     */
    protected $user_org_memberships;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstreams = $this->getMockBuilder(Upstreams::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstream->id = 'upstream_id';

        $this->command = new CreateCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->command->setConfig($this->getConfig());
        $this->command->setContainer($this->getContainer());
    }

    /**
     * Tests the site:create command
     */
    public function testCreate()
    {
        $site_name = 'site_name';
        $label = 'label';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow2 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->expects($this->once())
            ->method('nameIsTaken')
            ->with($this->equalTo($site_name))
            ->willReturn(false);

        $this->expectUpstreams();
        $this->sites->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['site_name' => $site_name, 'label' => $label, 'preferred_zone' => 'eu']))
            ->willReturn($workflow);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Creating a new site...')
            );

        $this->expectWorkflowProcessing();
        $workflow->expects($this->once())
            ->method('get')
            ->with($this->equalTo('waiting_for_task'))
            ->willReturn((object)['site_id' => 'site UUID',]);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Deploying CMS...')
            );
        $this->site->expects($this->once())
            ->method('deployProduct')
            ->with($this->equalTo($this->upstream->id))
            ->willReturn($workflow2);
        $this->logger->expects($this->at(2))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Deployed CMS')
            );

        $out = $this->command->create($site_name, $label, 'upstream', ['org' => null, 'region' => 'eu']);
        $this->assertNull($out);
    }

    /**
     * Tests the site:create command when the site name already exists
     */
    public function testCreateDuplicate()
    {
        $site_name = 'site_name';

        $this->sites->expects($this->once())
            ->method('nameIsTaken')
            ->with($this->equalTo($site_name))
            ->willReturn(true);

        $this->expectWorkflowProcessing();
        $this->setExpectedException(TerminusException::class, "The site name $site_name is already taken.");

        $out = $this->command->create($site_name, $site_name, 'upstream');
        $this->assertNull($out);
    }

    /**
     * Tests the site:create command when associating the new site with an organization
     */
    public function testCreateInOrg()
    {
        $site_name = 'site_name';
        $label = 'label';
        $org_name = 'org name';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow2 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $organization->id = 'org_id';

        $this->sites->expects($this->once())
            ->method('nameIsTaken')
            ->with($this->equalTo($site_name))
            ->willReturn(false);

        $this->expectUpstreams();
        $this->user->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($user_org_memberships);
        $user_org_memberships->expects($this->once())
            ->method('get')
            ->with($this->equalTo($org_name))
            ->willReturn($user_org_membership);
        $user_org_membership->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($organization);

        $this->sites->expects($this->once())
            ->method('create')
            ->with($this->equalTo([
                'site_name' => $site_name,
                'label' => $label,
                'organization_id' => $organization->id,
            ]))
            ->willReturn($workflow);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Creating a new site...')
            );

        $this->config->expects($this->at(0))
            ->method('get')
            ->with('command_site_options_region')
            ->willReturn(null);
        $this->expectContainerRetrieval();
        $this->expectProgressBarCycling();

        $workflow->expects($this->once())
            ->method('get')
            ->with($this->equalTo('waiting_for_task'))
            ->willReturn((object)['site_id' => 'site UUID',]);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Deploying CMS...')
            );
        $this->site->expects($this->once())
            ->method('deployProduct')
            ->with($this->equalTo($this->upstream->id))
            ->willReturn($workflow2);
        $this->logger->expects($this->at(2))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Deployed CMS')
            );

        $out = $this->command->create($site_name, $label, 'upstream', ['org' => $org_name,]);
        $this->assertNull($out);
    }

    protected function expectUpstreams()
    {
        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getUpstreams')
            ->with()
            ->willReturn($this->upstreams);
        $this->upstreams->expects($this->once())
            ->method('get')
            ->willReturn($this->upstream);
    }
}
