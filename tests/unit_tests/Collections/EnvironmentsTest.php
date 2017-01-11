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

    public function testIDs()
    {
        $this->makeEnvironmentsFetchable();
        $out = $this->collection->ids();
        $this->assertEquals($out, ['dev', 'test', 'live', 'multidev', 'new_version', 'multidev2',]);
    }

    protected function makeEnvironmentsFetchable()
    {
        $envs = [
            'multidev' => (object)[],
            'test' => (object)[],
            'dev' => (object)[],
            'new_version' => (object)[],
            'live' => (object)[],
            'multidev2' => (object)[],
        ];
        $ids = array_keys($envs);
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/environments"),
                $this->equalTo(['options' => ['method' => 'get',],])
            )
            ->willReturn(['data' => $envs,]);
        for ($i = 0; $i > count($ids); $i++) {
            $env = $this->getMockBuilder(Environment::class)
                ->disableOriginalConstructor()
                ->getMock();
            $env->id = $ids[$i];
            $env->method('isMultidev')->willReturn(in_array($env->id, ['dev', 'test', 'live',]));
            $this->container->expects($this->at($i))
                ->method('get')
                ->willReturn($env);
        }
    }
}
