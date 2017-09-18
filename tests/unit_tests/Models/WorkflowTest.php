<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\WorkflowOperations;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Models\WorkflowOperation;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;

/**
 * Class WorkflowTest
 * Testing class for Pantheon\Terminus\Models\Workflow
 * @package Pantheon\Terminus\UnitTests\Models
 */
class WorkflowTest extends ModelTestCase
{
    /**
     * Tests the Workflow::checkProgress() function
     */
    public function testCheckProgress()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site id';
        $workflow_id = 'workflow id';
        $workflow = new Workflow(
            (object)['id' => $workflow_id,],
            ['site' => $site,]
        );

        $this->request->expects($this->once())
            ->method('request')
            ->willReturn(['data' => ['result' => 'succeeded',],]);

        $workflow->setRequest($this->request);

        $this->assertTrue($workflow->checkProgress());
    }

    /**
     * Tests the Workflow::checkProgress() function when the workflow has failed
     */
    public function testCheckProgressFailure()
    {
        $message = 'reason message';
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site id';
        $workflow_id = 'workflow id';
        $workflow = new Workflow((object)['id' => $workflow_id,], ['site' => $site,]);
        $final_task = (object)[
            'messages' => ['message' => (object)['message' => ['message'],],],
            'reason' => $message,
        ];

        $this->request->expects($this->at(0))
            ->method('request')
            ->willReturn(['data' => ['result' => null,],]);
        $this->request->expects($this->at(1))
            ->method('request')
            ->willReturn(['data' => ['result' => 'failed', 'final_task' => $final_task,],]);

        $this->setExpectedException(TerminusException::class, $message);

        $workflow->setRequest($this->request);
        $this->assertFalse($workflow->checkProgress());
        $this->assertNull($workflow->checkProgress());
    }

    /**
     * Tests the response of the Workflow constructor when it is not given an owner object
     */
    public function testConstructWithoutOwner()
    {
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflows->expects($this->once())
            ->method('getOwnerObject')
            ->will($this->throwException(new \Exception('exception message')));

        $this->setExpectedException(TerminusException::class, 'Could not locate an owner for this Workflow object.');

        new Workflow((object)['id' => 'workflow id',], ['collection' => $workflows,]);
    }

    /**
     * Tests the Workflow::fetchWithLogs() and ::getUrl() functions
     */
    public function testFetchWithLogs()
    {
        $data = ['id' => 'workflow_id',];

        $site = new Site((object)['id' => 'site_id',]);
        $environments = new Environments(['site' => $site,]);
        $env = new Environment((object)['id' => 'env_id',], ['collection' => $environments,]);
        $user = new User((object)['id' => 'user_id',]);
        $org = new Organization((object)['id' => 'org_id',]);

        $workflow = new Workflow((object)$data, ['environment' => $env,]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('sites/site_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs',],])
            ->willReturn(['data' => ['baz' => '123',],]);
        $workflow->setRequest($this->request);
        $workflow->fetchWithLogs();

        $workflow = new Workflow((object)$data, ['site' => $site,]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('sites/site_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs',],])
            ->willReturn(['data' => ['baz' => '123',],]);
        $workflow->setRequest($this->request);
        $workflow->fetchWithLogs();

        $workflow = new Workflow((object)$data, ['user' => $user,]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('users/user_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs',],])
            ->willReturn(['data' => ['baz' => '123',],]);
        $workflow->setRequest($this->request);
        $workflow->fetchWithLogs();

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $workflow = new Workflow((object)$data, ['organization' => $org,]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('users/user_id/organizations/org_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs',],])
            ->willReturn(['data' => ['baz' => '123',],]);
        $workflow->setSession($session);
        $workflow->setRequest($this->request);
        $workflow->fetchWithLogs();
    }

    /**
     * Tests the Workflow::getMessage() function when the workflow has not succeeded
     */
    public function testGetMessageUnsuccessful()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow_id = 'workflow id';
        $active_description = 'active description';
        $final_task = (object)[
            'messages' => ['message' => (object)['message' => ['message'],],],
        ];
        $workflow = new Workflow((object)[
            'id' => $workflow_id,
            'final_task' => $final_task,
            'result' => 'not success',
            'active_description' => $active_description,
        ], ['site' => $site,]);

        $out = $workflow->getMessage();
        $this->assertEquals(print_r($final_task->messages['message']->message, true), $out);
    }

    /**
     * Tests the Workflow::getMessage() function when the workflow has not succeeded and has a reason
     */
    public function testGetMessageUnsuccessfulWithReason()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow_id = 'workflow id';
        $active_description = 'active description';
        $final_task = (object)[
            'messages' => ['message' => (object)['message' => ['message'],],],
            'reason' => 'reason',
        ];
        $workflow = new Workflow((object)[
            'id' => $workflow_id,
            'final_task' => $final_task,
            'result' => 'not success',
            'active_description' => $active_description,
        ], ['site' => $site,]);

        $out = $workflow->getMessage();
        $this->assertEquals($final_task->reason, $out);
    }

    /**
     * Tests the Workflow::getMessage() function when the workflow has succeeded
     */
    public function testGetMessageSuccess()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow_id = 'workflow id';
        $active_description = 'active description';
        $final_task = (object)[
            'messages' => ['message' => (object)['message' => ['message'],],],
        ];
        $workflow = new Workflow((object)[
            'id' => $workflow_id,
            'final_task' => $final_task,
            'result' => 'succeeded',
            'active_description' => $active_description,
        ], ['site' => $site,]);

        $out = $workflow->getMessage();
        $this->assertEquals($active_description, $out);
    }

    /**
     * Tests the Workflow::getOperations() function
     */
    public function testGetOperations()
    {
        $operations = [
            (object)['type' => 'Type', 'result' => 'Result', 'duration' => 'Duration', 'description' => 'Description'],
            (object)['type' => 'Art', 'result' => 'Ergebnis', 'duration' => 'Dauer', 'description' => 'Beschreibung'],
            (object)['type' => 'Taipkaan', 'result' => 'Keputusan', 'duration' => 'Tempoh', 'description' => 'Penerangan'],
            (object)['type' => 'tipe', 'result' => 'gevolg', 'duration' => 'duur', 'description' => 'beskrywing'],
            (object)['type' => 'Turi', 'result' => 'Natija', 'duration' => 'Muddati', 'description' => 'Ta\'rif'],
        ];
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wf_operations = $this->getMockBuilder(WorkflowOperations::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(WorkflowOperations::class, [['data' => $operations,]])
            ->willReturn($wf_operations);

        $workflow = new Workflow((object)['id' => '123', 'operations' => $operations,], ['site' => $site,]);
        $workflow->setContainer($container);

        $this->assertEquals($wf_operations, $workflow->getOperations());
    }

    /**
     * Tests the Workflow::getUrl() function when accessed multiple times
     */
    public function testGetUrlDuplicate()
    {
        $data = ['id' => 'workflow_id',];
        $site = new Site((object)['id' => 'site_id',]);
        $environments = new Environments(['site' => $site,]);
        $env = new Environment((object)['id' => 'env_id',], ['collection' => $environments,]);

        $workflow = new Workflow((object)$data, ['environment' => $env,]);
        $url = $workflow->getUrl();
        $this->assertEquals($url, $workflow->getUrl());
    }

    /**
     * Tests the Workflow::operations() function
     * @deprecated 1.5.1-dev
     */
    public function testOperations()
    {
        $operations = [
            (object)['type' => 'Type', 'result' => 'Result', 'duration' => 'Duration', 'description' => 'Description'],
            (object)['type' => 'Art', 'result' => 'Ergebnis', 'duration' => 'Dauer', 'description' => 'Beschreibung'],
            (object)['type' => 'Taipkaan', 'result' => 'Keputusan', 'duration' => 'Tempoh', 'description' => 'Penerangan'],
            (object)['type' => 'tipe', 'result' => 'gevolg', 'duration' => 'duur', 'description' => 'beskrywing'],
            (object)['type' => 'Turi', 'result' => 'Natija', 'duration' => 'Muddati', 'description' => 'Ta\'rif'],
        ];
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wf_operations = $this->getMockBuilder(WorkflowOperations::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wf_operation = $this->getMockBuilder(WorkflowOperation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(WorkflowOperations::class, [['data' => $operations,]])
            ->willReturn($wf_operations);
        $wf_operations->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$wf_operation, $wf_operation,]);

        $workflow = new Workflow((object)['id' => '123', 'operations' => $operations,], ['site' => $site,]);
        $workflow->setContainer($container);

        $this->assertEquals([$wf_operation, $wf_operation,], $workflow->operations());
    }

    /**
     * Tests the Workflow::serialize() function
     */
    public function testSerialize()
    {
        $workflow_description = 'workflow description';
        $env = 'some env';
        $email = 'handle@domain.ext';
        $workflow_id = 'workflow id';
        $status = 'succeeded';
        $operations = [
            (object)['type' => 'Type', 'result' => 'Result', 'duration' => 'Duration', 'description' => 'Description'],
            (object)['type' => 'Art', 'result' => 'Ergebnis', 'duration' => 'Dauer', 'description' => 'Beschreibung'],
            (object)['type' => 'Taipkaan', 'result' => 'Keputusan', 'duration' => 'Tempoh', 'description' => 'Penerangan'],
            (object)['type' => 'tipe', 'result' => 'gevolg', 'duration' => 'duur', 'description' => 'beskrywing'],
            (object)['type' => 'Turi', 'result' => 'Natija', 'duration' => 'Muddati', 'description' => 'Ta\'rif'],
        ];
        $operations_serialized = [
            ['type' => 'Type', 'result' => 'Result', 'duration' => 'Duration', 'description' => 'Description'],
            ['type' => 'Art', 'result' => 'Ergebnis', 'duration' => 'Dauer', 'description' => 'Beschreibung'],
            ['type' => 'Taipkaan', 'result' => 'Keputusan', 'duration' => 'Tempoh', 'description' => 'Penerangan'],
            ['type' => 'tipe', 'result' => 'gevolg', 'duration' => 'duur', 'description' => 'beskrywing'],
            ['type' => 'Turi', 'result' => 'Natija', 'duration' => 'Muddati', 'description' => 'Ta\'rif'],
        ];
        $data = (object)[
            'id' => $workflow_id,
            'description' => $workflow_description,
            'environment' => $env,
            'result' => $status,
            'created_at' => 0,
            'finished_at' => 1,
            'started_at' => 0,
            'user' => (object)compact('email'),
            'operations' => $operations,
        ];
        $expected = [
            'id' => $workflow_id,
            'env' => $env,
            'workflow' => $workflow_description,
            'user' => $email,
            'status' => $status,
            'finished_at' => 1,
            'started_at' => 0,
            'time' => time() . 's',
            'operations' => $operations_serialized,
        ];


        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wf_operations = $this->getMockBuilder(WorkflowOperations::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(WorkflowOperations::class, [['data' => $operations,]])
            ->willReturn($wf_operations);
        $wf_operations->expects($this->once())
            ->method('serialize')
            ->willReturn($operations_serialized);

        $workflow = new Workflow($data, compact('site'));
        $workflow->setContainer($container);

        $this->assertEquals($expected, $workflow->serialize());
    }

    /**
     * Tests the Workflow::status() functions
     */
    public function testStatus()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = new Workflow(['id' => '123',], ['site' => $site,]);
        $this->assertEquals('running', $workflow->getStatus());
        $this->assertEquals(false, $workflow->isSuccessful());
        $this->assertEquals(false, $workflow->isFinished());

        $workflow->set('result', 'succeeded');
        $this->assertEquals('succeeded', $workflow->getStatus());
        $this->assertEquals(true, $workflow->isSuccessful());
        $this->assertEquals(true, $workflow->isFinished());

        $workflow->set('result', 'failed');
        $this->assertEquals('failed', $workflow->getStatus());
        $this->assertEquals(false, $workflow->isSuccessful());
        $this->assertEquals(true, $workflow->isFinished());
    }
}
