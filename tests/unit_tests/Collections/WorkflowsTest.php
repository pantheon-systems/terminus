<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;

/**
 * Class WorkflowsTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class WorkflowsTest extends CollectionTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tests several workflow array-getting functions:
     * allFinished
     * allWithLogs
     * findLatestWithLogs
     * lastCreatedAt
     * lastFinishedAt
     */
    public function testAll()
    {
        $data = [
            'a' => ['id' => 'a', 'has_operation_log_output' => true, 'result' => 'succeeded', 'finished_at' => 4, 'created_at' => 1],
            'b' => ['id' => 'b', 'has_operation_log_output' => false, 'result' => 'failed', 'finished_at' => 5, 'created_at' => 4],
            'c' => ['id' => 'c', 'has_operation_log_output' => true, 'finished_at' => 2, 'created_at' => 3],
            'd' => ['id' => 'd', 'has_operation_log_output' => true, 'result' => 'succeeded', 'finished_at' => 1, 'created_at' => 2],
        ];

        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->setMethods(['all'])
            ->getMock();

        $models = [];
        foreach ($data as $id => $model_data) {
            $models[$id] = new Workflow((object)$model_data, ['collection' => $workflows,]);
        }
        $workflows->expects($this->any())
            ->method('all')
            ->willReturn($models);

        $this->assertEquals($models, $workflows->all());
        $this->assertEquals([$models['a'], $models['b'], $models['d']], array_values($workflows->allFinished()));
        $this->assertEquals([$models['a'], $models['d']], array_values($workflows->allWithLogs()));
        $this->assertEquals($models['a'], $workflows->findLatestWithLogs());
        $this->assertEquals($data['b']['created_at'], $workflows->lastCreatedAt());
        $this->assertEquals($data['b']['finished_at'], $workflows->lastFinishedAt());
    }

    /**
     * Tests several workflows collection evaluation functions when there are no workflows
     * lastCreatedAt
     * lastFinishedAt
     */
    public function testAllComesBackEmpty()
    {
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->setMethods(['all',])
            ->getMock();

        $workflows->expects($this->any())
            ->method('all')
            ->willReturn([]);

        $this->assertNull($workflows->lastCreatedAt());
        $this->assertNull($workflows->lastFinishedAt());
    }

    /**
     * Tests several workflow array-getting functions when returning nulls because no workflows fit the criteria:
     * allFinished
     * allWithLogs
     * findLatestWithLogs
     */
    public function testAllIncompleteAndWithoutLogs()
    {
        $data = [
            ['id' => 'a', 'has_operation_log_output' => false,],
            ['id' => 'b', 'has_operation_log_output' => false,],
            ['id' => 'c', 'has_operation_log_output' => false,],
            ['id' => 'd', 'has_operation_log_output' => false,],
        ];

        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->setMethods(['all'])
            ->getMock();

        $models = [];
        foreach ($data as $model_data) {
            $models[] = new Workflow((object)$model_data, ['collection' => $workflows]);
        }
        $workflows->expects($this->any())
            ->method('all')
            ->willReturn($models);

        $this->assertEquals($models, $workflows->all());
        $this->assertEquals([], $workflows->allFinished());
        $this->assertEquals([], $workflows->allWithLogs());
        $this->assertNull($workflows->findLatestWithLogs());
    }

    public function testCreate()
    {
        $type = "test";
        $model_data = (object)[
            'id' => 'a',
        ];
        $params = ['a' => '1', 'b' => 2];
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'TESTURL',
                [
                    'method' => 'post',
                    'form_params' => [
                        'type' => $type,
                        'params' => (object)$params,
                    ],
                ]
            )
            ->willReturn(['data' => $model_data]);

        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl', 'add'])
            ->getMock();
        $workflows->expects($this->once())
            ->method('getURl')
            ->willReturn('TESTURL');

        $options = ['id' => $model_data->id, 'collection' => $workflows];
        $model = new Workflow($model_data, $options);
        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Workflow::class, [$model_data, $options])
            ->willReturn($model);

        $workflows->expects($this->once())
            ->method('add')
            ->willReturn($model);

        $workflows->setRequest($this->request);
        $workflows->setContainer($this->container);

        $workflows->create('test', ['params' => $params]);
    }

    public function testFetchWithOperations()
    {
        $data = [
            (object)['id' => 'a', 'result' => 'succeeded', 'finished_at' => 4, 'created_at' => 1],
            (object)['id' => 'b', 'result' => 'failed', 'finished_at' => 5, 'created_at' => 4],
            (object)['id' => 'c', 'finished_at' => 2, 'created_at' => 3],
        ];
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'TESTURL',
                [
                    'options' => [
                        'method' => 'get',
                    ],
                    'query' => ['hydrate' => 'operations'],
                ]
            )
            ->willReturn(['data' => $data]);
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl', 'add'])
            ->getMock();
        $workflows->expects($this->once())
            ->method('getURl')
            ->willReturn('TESTURL');

        foreach ($data as $i => $model_data) {
            $workflows->expects($this->at($i+1))
                ->method('add')
                ->with($model_data);
        }

        $workflows->setRequest($this->request);

        $workflows->fetchWithOperations();
    }

    public function testGetOwnerObject()
    {
        $site = new Site((object)['id' => 'site_id']);
        $environments = new Environments(['site' => $site]);
        $env = new Environment((object)['id' => 'env_id'], ['collection' => $environments]);
        $user = new User((object)['id' => 'user_id']);
        $org = new Organization((object)['id' => 'org_id']);

        $workflows = new Workflows(['environment' => $env]);
        $this->assertEquals($env, $workflows->getOwnerObject());
        $this->assertEquals('sites/site_id/environments/env_id/workflows', $workflows->getUrl());

        $workflows = new Workflows(['site' => $site]);
        $this->assertEquals($site, $workflows->getOwnerObject());
        $this->assertEquals('sites/site_id/workflows', $workflows->getUrl());

        $workflows = new Workflows(['user' => $user]);
        $this->assertEquals($user, $workflows->getOwnerObject());
        $this->assertEquals('users/user_id/workflows', $workflows->getUrl());

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $workflows = new Workflows(['organization' => $org]);
        $workflows->setSession($session);
        $this->assertEquals($org, $workflows->getOwnerObject());
        $this->assertEquals('users/user_id/organizations/org_id/workflows', $workflows->getUrl());
    }

    /**
     * Tests Workflows::getUrl when the url property has a value
     */
    public function testGetUrl()
    {
        $site = new Site((object)['id' => 'site_id',]);
        $environments = new Environments(['site' => $site,]);
        $env = new Environment((object)['id' => 'env_id',], ['collection' => $environments,]);
        $workflows = new Workflows(['environment' => $env,]);
        $url1 = $workflows->getUrl(); // Assigns the value to the property
        $url2 = $workflows->getUrl(); // Returns the already-assigned value of that property
        $this->assertEquals($url1, $url2);
    }
}
