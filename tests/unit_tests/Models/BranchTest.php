<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Branch;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class BranchTest
 * Testing class for Pantheon\Terminus\Models\Branch
 * @package Pantheon\Terminus\UnitTests\Models
 */
class BranchTest extends ModelTestCase
{
    /**
     * @var Site
     */
    protected $site;
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
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site_id';
        $site->method('getWorkflows')->willReturn($this->workflows);
        $this->collection = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->method('getSite')->willReturn($site);

        $this->model = new Branch((object)['id' => 'branch_id', 'sha' => 'sha',], ['collection' => $this->collection,]);
        $this->model->setRequest($this->request);
    }

    /**
     * Tests Branch::delete()
     */
    public function testDelete()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('delete_environment_branch'),
                $this->equalTo(['params' => ['environment_id' => $this->model->id,],])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->delete();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Branch::serialize()
     */
    public function testSerialize()
    {
        $data = $this->model->fetch()->serialize();
        $this->assertEquals(['id' => $this->model->id, 'sha' => 'sha',], $data);
    }
}
