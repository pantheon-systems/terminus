<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Terminus\Collections\SiteOrganizationMemberships;
use Terminus\Collections\Workflows;
use Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Terminus\Models\SiteOrganizationMembership;
use Terminus\Models\Workflow;

/**
 * Testing class for Terminus\Models\Site
 */
class SiteTest extends ModelTestCase
{
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

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Site((object)['id' => 123,]);

        $container->method('get')
            ->with(Workflows::class, [['site' => $this->model]])
            ->willReturn($this->workflows);

        $this->model->setContainer($container);

        $this->model->setRequest($this->request);
    }

    /**
     * Tests Site::addInstrument($instrument_id)
     */
    public function testAddInstrument()
    {
        $instrument_id = 'instrument_id';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('associate_site_instrument'),
                $this->equalTo(
                    [
                        'site' => $this->model->id,
                        'params' => compact('instrument_id'),
                    ]
                )
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->addInstrument($instrument_id);
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
        $this->model->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model->org_memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$org_membership,]);

        $data = [$org_membership->organization->id => $org_membership->organization,];

        $orgs = $this->model->getOrganizations();
        $this->assertEquals($data, $orgs);
    }

    /**
     * Tests Site::removeInstrument()
     */
    public function testRemoveInstrument()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('disassociate_site_instrument'),
                $this->equalTo(['site' => $this->model->id,])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->removeInstrument();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Site::serialize()
     */
    public function testSerialize()
    {
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
            'upstream' => ': ',
            'php_version' => '7.5',
            'holder_type' => 'holder type',
            'holder_id' => 'holder id',
            'owner' => 'owner id',
            'frozen' => true,
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo("sites/{$this->model->id}?site_state=true"))
            ->willReturn(compact('data'));

        $returned_data = $this->model->fetch()->serialize();
        $this->assertEquals($returned_data, $expected_data);
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
}
