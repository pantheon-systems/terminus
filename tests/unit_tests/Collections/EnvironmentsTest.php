<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use League\Container\Container;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Request\Request;

/**
 * Class EnvironmentsTest
 * Testing class for Pantheon\Terminus\Collections\Environments
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class EnvironmentsTest extends CollectionTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var object[]
     */
    protected $env_data;
    /**
     * @var Environment[]
     */
    protected $environments;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Site
     */
    protected $site;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'site id';
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = new Environments(['site' => $this->site,]);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($this->container);
    }

    /**
     * Tests the Environments::create(string, Environment) function
     */
    public function testCreate()
    {
        $to_env_string = 'to env';
        $from_env = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $from_env->id = 'from env id';
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($workflows);
        $workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('create_cloud_development_environment'),
                $this->equalTo([
                    'params' => [
                        'environment_id' => $to_env_string,
                        'deploy' => [
                            'clone_database' => ['from_environment' => $from_env->id,],
                            'clone_files' => ['from_environment' => $from_env->id,],
                            'annotation' => sprintf(
                                'Create the "%s" environment.',
                                $to_env_string
                            ),
                        ],
                    ],
                ])
            )
            ->willReturn($workflow);

        $out = $this->collection->create($to_env_string, $from_env);
        $this->assertEquals($out, $workflow);
    }

    /**
     * Tests the Environments::ids() function
     */
    public function testIDs()
    {
        $this->makeEnvironmentsFetchable();
        $out = $this->collection->ids();
        $this->assertEquals($out, ['dev', 'test', 'live', 'multidev', 'new_version', 'multidev2',]);
    }

    /**
     * Tests the Environments::multidev() function
     */
    public function testMultidev()
    {
        $this->makeEnvironmentsFetchable();
        $expected = [
            'multidev' => $this->environments['multidev'],
            'multidev2' => $this->environments['multidev2'],
            'new_version' => $this->environments['new_version'],
        ];
        $this->assertEquals($expected, $this->collection->multidev());
    }

    /**
     * Tests the Environments::serialize() function when the site is frozen
     */
    public function testSerializeFrozen()
    {
        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(true);
        $this->makeEnvironmentsFetchable();
        $expected = array_map(function ($data) {
            return (array)$data;
        }, $this->env_data);
        unset($expected['test']);
        unset($expected['live']);
        $this->assertEquals($expected, $this->collection->serialize());
    }

    /**
     * Tests the Environments::serialize() function when the site is not frozen
     */
    public function testSerializeUnfrozen()
    {
        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(false);
        $this->makeEnvironmentsFetchable();
        $expected = array_map(function ($env) {
            return (array)$env;
        }, $this->env_data);
        $this->assertEquals($expected, $this->collection->serialize());
    }

    protected function makeEnvironmentsFetchable()
    {
        $this->env_data = [
            'multidev' => (object)['key' => 'value',],
            'test' => (object)['key' => 'foo',],
            'dev' => (object)['key' => 'bar',],
            'new_version' => (object)['key' => null,],
            'live' => (object)['key' => 'hello',],
            'multidev2' => (object)['key' => 'C',],
        ];
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/environments"),
                $this->equalTo(['options' => ['method' => 'get',],])
            )
            ->willReturn(['data' => $this->env_data,]);
        $i = 0;
        foreach ($this->env_data as $id => $attributes) {
            $env = $this->getMockBuilder(Environment::class)
                ->disableOriginalConstructor()
                ->getMock();
            $env->id = $id;
            $env->method('isMultidev')
                ->willReturn(!in_array($env->id, ['dev', 'test', 'live',]));
            $env->method('serialize')
                ->willReturn((array)$attributes);
            $this->container->expects($this->at($i))
                ->method('get')
                ->willReturn($env);
            $this->environments[$env->id] = $env;
            $i++;
        }
    }
}
