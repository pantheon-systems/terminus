<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Environments;
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
     * @var Workflow
     */
    protected $workflow;

    public function setUp()
    {
        parent::setUp();

        $this->workflow = new Workflow(['id' => '123']);
    }

    public function testStatus()
    {
        $this->assertEquals('running', $this->workflow->getStatus());
        $this->assertEquals(false, $this->workflow->isSuccessful());
        $this->assertEquals(false, $this->workflow->isFinished());


        $this->workflow->set('result', 'succeeded');
        $this->assertEquals('succeeded', $this->workflow->getStatus());
        $this->assertEquals(true, $this->workflow->isSuccessful());
        $this->assertEquals(true, $this->workflow->isFinished());

        $this->workflow->set('result', 'failed');
        $this->assertEquals('failed', $this->workflow->getStatus());
        $this->assertEquals(false, $this->workflow->isSuccessful());
        $this->assertEquals(true, $this->workflow->isFinished());
    }

    public function testOperations()
    {
        $operations = [
            ['id' => 'bar', 'description' => 'Dumbo Drop',],
            ['id' => 'baz', 'description' => 'Dumbo Pick Back Up Again',],
        ];
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($operations as $i => $op) {
            $container->expects($this->at($i))
                ->method('get')
                ->with(WorkflowOperation::class, [$op])
                ->willReturn(new WorkflowOperation($op));
        }

        $this->workflow->setContainer($container);
        $this->workflow->set('operations', $operations);

        $this->workflow->operations();
    }

    /**
     *
     */
    public function testFetchWithLogs()
    {
        $data = ['id' => 'workflow_id'];

        $site = new Site((object)['id' => 'site_id']);
        $environments = new Environments(['site' => $site]);
        $env = new Environment((object)['id' => 'env_id'], ['collection' => $environments]);
        $user = new User((object)['id' => 'user_id']);
        $org = new Organization((object)['id' => 'org_id']);


        $this->workflow = new Workflow((object)$data, ['environment' => $env]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('sites/site_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs']])
            ->willReturn(['data' => ['baz' => '123']]);
        $this->workflow->setRequest($this->request);
        $this->workflow->fetchWithLogs();

        $this->workflow = new Workflow((object)$data, ['site' => $site]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('sites/site_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs']])
            ->willReturn(['data' => ['baz' => '123']]);
        $this->workflow->setRequest($this->request);
        $this->workflow->fetchWithLogs();

        $this->workflow = new Workflow((object)$data, ['user' => $user]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('users/user_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs']])
            ->willReturn(['data' => ['baz' => '123']]);
        $this->workflow->setRequest($this->request);
        $this->workflow->fetchWithLogs();

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->workflow = new Workflow((object)$data, ['organization' => $org]);
        $this->request->expects($this->at(0))
            ->method('request')
            ->with('users/user_id/organizations/org_id/workflows/workflow_id', ['options' => ['method' => 'get',], 'query' => ['hydrate' => 'operations_with_logs']])
            ->willReturn(['data' => ['baz' => '123']]);
        $this->workflow->setSession($session);
        $this->workflow->setRequest($this->request);
        $this->workflow->fetchWithLogs();
    }

    // @TODO: Test the waiting and get message functions once they've been unwound into something testable.
}
