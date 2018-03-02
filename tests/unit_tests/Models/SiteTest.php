<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\NewRelic;
use Pantheon\Terminus\Models\Redis;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Solr;
use Pantheon\Terminus\Models\SiteUpstream;
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
     * @var SiteOrganizationMemberships
     */
    protected $org_memberships;
    /**
     * @var Redis
     */
    protected $redis;
    /**
     * @var array
     */
    protected $site_data;
    /**
     * @var Solr
     */
    protected $solr;
    /**
     * @var SiteUpstream
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

        $this->branches = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->branches = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = new Container();
        $this->environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->new_relic = $this->getMockBuilder(NewRelic::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redis = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->solr = $this->getMockBuilder(Solr::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstream = $this->getMockBuilder(SiteUpstream::class)
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

        $this->site_data = (object)['id' => 'site id', 'name' => 'my-site', 'label' => 'My Site',];

        $this->container->add(Branches::class, $this->branches);
        $this->container->add(Environments::class, $this->environments);
        $this->container->add(SiteOrganizationMemberships::class, $this->org_memberships);
        $this->container->add(NewRelic::class, $this->new_relic);
        $this->container->add(Redis::class, $this->redis);
        $this->container->add(SiteUserMemberships::class, $this->user_memberships);
        $this->container->add(Solr::class, $this->solr);
        $this->container->add(SiteUpstream::class, $this->upstream);
        $this->container->add(Workflows::class, $this->workflows);

        $this->model = new Site($this->site_data);

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
        $this->configSet(['dashboard_protocol' => 'https', 'dashboard_host' => 'dashboard.pantheon.io',]);
        $this->assertEquals("https://dashboard.pantheon.io/sites/" . $this->site_data->id, $this->model->dashboardUrl());
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
     * Tests Site::deployProduct($upstream_id)
     */
    public function testSetUpstream()
    {
        $upstream_id = 'upstream_id';

        $this->workflows->expects($this->once())
          ->method('create')
          ->with(
              $this->equalTo('switch_upstream'),
              $this->equalTo(['params' => ['upstream_id' => $upstream_id,],])
          )
          ->willReturn($this->workflow);

        $workflow = $this->model->setUpstream($upstream_id);
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
     * Tests Site::getEnvironments()
     */
    public function testUnsetEnvironments()
    {
        $container = $this->getMockBuilder(Container::class)
            ->setMethods(['get'])
            ->getMock();

        $model = new Site($this->site_data);

        $model->setContainer($container);
        $model->setRequest($this->request);
        $model->setConfig($this->config);

        // We can call 'getEnvironments()' as many times as we like;
        // it will not be re-fetched from the container until after
        // unsetEnvironments() is called.
        $container->expects($this->exactly(2))
            ->method('get')
            ->with(
                $this->equalTo(Environments::class),
                $this->equalTo([['site' => $model,],])
            )
            ->willReturn($this->environments);

        // First call fetches from container
        $environments = $model->getEnvironments();

        // Does not fetch from container
        $environments = $model->getEnvironments();

        // Erases Site::$environments
        $model->unsetEnvironments();

        // Re-fetches environments from container
        $environments = $model->getEnvironments();

        // Does not fetch from container
        $environments = $model->getEnvironments();
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
        $this->assertEquals($this->site_data->name, $this->model->getName());
    }

    /**
     * Tests Site::getOrganizationMemberships() and OrganizationsTrait::getOrgMemberships()
     */
    public function testGetOrganizationMemberships()
    {
        $this->assertEquals($this->org_memberships, $this->model->getOrganizationMemberships());
        $this->assertEquals($this->org_memberships, $this->model->getOrgMemberships());
    }

    /**
     * Tests Site::getReferences()
     */
    public function testGetReferences()
    {
        $this->assertEquals(array_values((array)$this->site_data), $this->model->getReferences());
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
     * Tests Site::isFrozen()
     */
    public function testIsFrozen()
    {
        $frozen = $this->model->isFrozen();
        $this->assertEquals(false, $frozen);

        $this->model->set('frozen', null);
        $frozen = $this->model->isFrozen();
        $this->assertEquals(false, $frozen);

        $this->model->set('frozen', true);
        $frozen = $this->model->isFrozen();
        $this->assertEquals(true, $frozen);

        $this->model->set('frozen', 'yes');
        $frozen = $this->model->isFrozen();
        $this->assertEquals(true, $frozen);
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
            'created' => '682641540',
            'framework' => 'framework name',
            'organization' => 'organization name',
            'service_level' => 'service level',
            'php_version' => '75',
            'holder_type' => 'holder type',
            'holder_id' => 'holder id',
            'owner' => 'owner id',
            'frozen' => 'yes',
            'last_frozen_at' => '1682641540',
        ];
        $expected_data = [
            'id' => $this->model->id,
            'name' => 'site name',
            'label' => 'site label',
            'created' => '1991-08-19 22:39:00',
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
            'max_num_cdes' => 0,
            'last_frozen_at' => '2023-04-28 00:25:40',
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
     * Tests Site::serialize() when the given datetime is not a Unix timestamp
     */
    public function testSerializeNotTimestamp()
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
            'created' => 'August 19, 1991 10:39PM',
            'framework' => 'framework name',
            'organization' => 'organization name',
            'service_level' => 'service level',
            'php_version' => '75',
            'holder_type' => 'holder type',
            'holder_id' => 'holder id',
            'owner' => 'owner id',
            'frozen' => 'yes',
            'last_frozen_at' => 'April 28, 2023 9:20PM',
        ];
        $expected_data = [
            'id' => $this->model->id,
            'name' => 'site name',
            'label' => 'site label',
            'created' => '1991-08-19 22:39:00',
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
            'max_num_cdes' => 0,
            'last_frozen_at' => '2023-04-28 21:20:00',
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
        $attributes = (object)[
            'id' => 'site_id',
            'product' => (object)['id' => 'product id',],
            'upstream' => (object)['product_id' => 'product id',],
        ];
        $site = new Site($attributes);
        $site->setContainer($container);
        $upstream = $this->getMockBuilder(SiteUpstream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(SiteUpstream::class),
                $this->equalTo([
                    (object)array_merge((array)$attributes->product, (array)$attributes->upstream),
                    compact('site'),
                ])
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
        $upstream = $this->getMockBuilder(SiteUpstream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(SiteUpstream::class),
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
