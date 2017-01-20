<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\NewRelic;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Redis;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\Solr;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteTest
 * Testing class for Pantheon\Terminus\Models\Site
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SiteTest extends ModelTestCase
{
    /**
     * @var Branches
     */
    protected $branches;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Environments
     */
    protected $environments;
    /**
     * @var NewRelic
     */
    protected $new_relic;
    /**
     * @var Redis
     */
    protected $redis;
    /**
     * @var Solr
     */
    protected $solr;
    /**
     * @var Upstream
     */
    protected $upstream;
    /**
     * @var SiteUserMemberships
     */
    protected $user_memberships;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = new Container();

        $this->branches = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->new_relic = $this->getMockBuilder(NewRelic::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redis = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->solr = $this->getMockBuilder(Solr::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->add(Branches::class, $this->branches);
        $this->container->add(Environments::class, $this->environments);
        $this->container->add(NewRelic::class, $this->new_relic);
        $this->container->add(Redis::class, $this->redis);
        $this->container->add(SiteUserMemberships::class, $this->user_memberships);
        $this->container->add(Solr::class, $this->solr);
        $this->container->add(Upstream::class, $this->upstream);
        $this->container->add(Workflows::class, $this->workflows);

        $this->model = new Site((object)['id' => 123, 'name' => 'My Site']);

        $this->model->setContainer($this->container);
        $this->model->setRequest($this->request);
        $this->model->setConfig($this->config);
    }

    /**
     * Tests Site::addPaymentMethod($payment_method_id)
     */
    public function testAddPaymentMethod()
    {
        $payment_method_id = 'payment_method_id';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('associate_site_instrument'),
                $this->equalTo(
                    [
                        'site' => $this->model->id,
                        'params' => ['instrument_id' => $payment_method_id,],
                    ]
                )
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->addPaymentMethod($payment_method_id);
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::completeMigration()
     */
    public function testCompleteMigration()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with($this->equalTo('complete_migration'))
            ->willReturn($this->workflow);

        $workflow = $this->model->completeMigration();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::converge()
     */
    public function testConverge()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with($this->equalTo('converge_site'))
            ->willReturn($this->workflow);

        $workflow = $this->model->converge();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::dashboardUrl()
     */
    public function testDashboardUrl()
    {
        $this->configSet(['dashboard_protocol' => 'https', 'dashboard_host' => 'dashboard.pantheon.io']);
        $this->assertEquals('https://dashboard.pantheon.io/sites/123', $this->model->dashboardUrl());
    }

    /**
     * Tests Site::delete()
     */
    public function testDelete()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->model->id}"),
                $this->equalTo(['method' => 'delete',])
            );
        $out = $this->model->delete();
        $this->assertNull($out);
    }

    /**
     * Tests Site::deployProduct($upstream_id)
     */
    public function testDeployProduct()
    {
        $upstream_id = 'upstream_id';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('deploy_product'),
                $this->equalTo(['params' => ['product_id' => $upstream_id,],])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->deployProduct($upstream_id);
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::fetch($options)
     */
    public function testFetch()
    {
        $data = [
            'id' => $this->model->id,
            'upstream' => (object)['id' => 'upstream_id',],
            'dummy_attribute' => 'dummy_value',
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo("sites/{$this->model->id}?site_state=true"))
            ->willReturn(compact('data'));

        $fetched_site = $this->model->fetch();
        $this->assertEquals($fetched_site, $this->model);
        $this->assertEquals($this->model->get('dummy_attribute'), $data['dummy_attribute']);
    }

    /**
     * Tests Site::getBranches()
     */
    public function testGetBranches()
    {
        $branches = $this->model->getBranches();
        $this->assertEquals($this->branches, $branches);
    }

    /**
     * Tests Site::getEnvironments()
     */
    public function testGetEnvironments()
    {
        $environments = $this->model->getEnvironments();
        $this->assertEquals($this->environments, $environments);
    }

    /**
     * Tests Site::getFeature($feature)
     */
    public function testGetFeature()
    {
        $key = 'dummy_feature';
        $data = (object)[$key => 'dummy_value',];

        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo("sites/{$this->model->id}/features"))
            ->willReturn(compact('data'));

        $feature_value = $this->model->getFeature($key);
        $this->assertEquals($feature_value, $data->$key);
    }

    /**
     * Tests Site::getFeature($feature) when the asked-for feature is not present
     */
    public function testGetFeatureDNE()
    {
        $data = (object)[];

        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo("sites/{$this->model->id}/features"))
            ->willReturn(compact('data'));

        $feature_value = $this->model->getFeature('invalid_key');
        $this->assertNull($feature_value);
    }

    /**
     * Tests Site::getName()
     */
    public function testGetName()
    {
        $this->assertEquals('My Site', $this->model->getName());
    }

    /**
     * Tests Site::getNewRelic()
     */
    public function testGetNewRelic()
    {
        $new_relic = $this->model->getNewRelic();
        $this->assertEquals($this->new_relic, $new_relic);
    }

    /**
     * Tests Site::getOrganizations()
     */
    public function testGetOrganizations()
    {
        $org_membership = $this->getMockBuilder(SiteOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org_membership->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org_membership->organization->id = 'organization_id';
        $this->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->org_memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$org_membership,]);

        $this->container->add(SiteOrganizationMemberships::class, $this->org_memberships);

        $data = [$org_membership->organization->id => $org_membership->organization,];

        $orgs = $this->model->getOrganizations();
        $this->assertEquals($data, $orgs);
    }

    /**
     * Tests Site::getRedis()
     */
    public function testGetRedis()
    {
        $redis = $this->model->getRedis();
        $this->assertEquals($this->redis, $redis);
    }

    /**
     * Tests Site::getSolr()
     */
    public function testGetSolr()
    {
        $solr = $this->model->getSolr();
        $this->assertEquals($this->solr, $solr);
    }

    /**
     * Tests Site::getUserMemberships()
     */
    public function testGetUserMemberships()
    {
        $user_memberships = $this->model->getUserMemberships();
        $this->assertEquals($this->user_memberships, $user_memberships);
    }

    /**
     * Tests Site::removePaymentMethod()
     */
    public function testRemovePaymentMethod()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('disassociate_site_instrument'),
                $this->equalTo(['site' => $this->model->id,])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->removePaymentMethod();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::serialize()
     */
    public function testSerialize()
    {
        $this->configSet(['date_format' => 'Y-m-d H:i:s',]);
        $this->model->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model->memberships = ['membership1', 'membership2',];
        $tags = ['tag1', 'tag2',];
        $data = (object)[
            'id' => $this->model->id,
            'name' => 'site name',
            'label' => 'site label',
            'created' => '-10318838400',
            'framework' => 'framework name',
            'organization' => 'organization name',
            'service_level' => 'service level',
            'php_version' => '75',
            'holder_type' => 'holder type',
            'holder_id' => 'holder id',
            'owner' => 'owner id',
            'frozen' => 'yes',
        ];
        $expected_data = [
            'id' => $this->model->id,
            'name' => 'site name',
            'label' => 'site label',
            'created' => '1643-01-04 00:00:00',
            'framework' => 'framework name',
            'organization' => 'organization name',
            'service_level' => 'service level',
            'upstream' => '***UPSTREAM***',
            'php_version' => '7.5',
            'holder_type' => 'holder type',
            'holder_id' => 'holder id',
            'owner' => 'owner id',
            'frozen' => 'true',
            'memberships' => implode(',', $this->model->memberships),
            'tags' => implode(',', $tags),
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo("sites/{$this->model->id}?site_state=true"))
            ->willReturn(compact('data'));
        $this->upstream->method('__toString')->willReturn('***UPSTREAM***');
        $this->model->tags->expects($this->once())
            ->method('ids')
            ->with()
            ->willReturn($tags);

        $returned_data = $this->model->fetch()->serialize();
        $this->assertEquals($expected_data, $returned_data);
    }

    /**
     * Tests Site::setOwner($user_id)
     */
    public function testSetOwner()
    {
        $user_id = 'user_id';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('promote_site_user_to_owner'),
                $this->equalTo(['params' => compact('user_id'),])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->setOwner($user_id);
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::setUpstream($attributes) when there is upstream info in the constructor attributes
     */
    public function testSetUpstreamFromConstructorAttr()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributes = (object)['id' => 'site_id', 'upstream' => (object)['product_id' => 'product id',],];
        $site = new Site($attributes);
        $site->setContainer($container);
        $upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Upstream::class),
                $this->equalTo([$attributes->upstream, compact('site'),])
            )
            ->willReturn($upstream);

        $out = $site->getUpstream();
        $this->assertEquals($upstream, $out);
    }

    /**
     * Tests Site::setUpstream($attributes) when there is upstream info in the constructor attributes' settings property
     */
    public function testSetUpstreamFromConstructorAttrSettings()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributes = (object)[
            'id' => 'site_id',
            'settings' => (object)['upstream' => (object)['product_id' => 'product id',],],
        ];
        $site = new Site($attributes);
        $site->setContainer($container);
        $upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Upstream::class),
                $this->equalTo([$attributes->settings->upstream, compact('site'),])
            )
            ->willReturn($upstream);

        $out = $site->getUpstream();
        $this->assertEquals($upstream, $out);
    }

    /**
     * Tests Site::updateServiceLevel($service_level)
     */
    public function testUpdateServiceLevel()
    {
        $service_level = 'service_level';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('change_site_service_level'),
                $this->equalTo(['params' => compact('service_level'),])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->updateServiceLevel($service_level);
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::updateServiceLevel($service_level) when there is no payment method available
     */
    public function testUpdateServiceLevelNoMethod()
    {
        $service_level = 'service_level';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('change_site_service_level'),
                $this->equalTo(['params' => compact('service_level'),])
            )
            ->will($this->throwException(new \Exception('message', 403)));

        $this->setExpectedException(
            TerminusException::class,
            'A payment method is required to increase the service level of this site.'
        );

        $out = $this->model->updateServiceLevel($service_level);
        $this->assertNull($out);
    }

    /**
     * Tests Site::updateServiceLevel($service_level) when a non-403 error occurs
     */
    public function testUpdateServiceLevelMiscError()
    {
        $service_level = 'service_level';
        $expected_exception = new \Exception('message', 0);

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('change_site_service_level'),
                $this->equalTo(['params' => compact('service_level'),])
            )
            ->will($this->throwException($expected_exception));

        $this->setExpectedException(get_class($expected_exception), $expected_exception->getMessage());

        $out = $this->model->updateServiceLevel($service_level);
        $this->assertNull($out);
    }
}
