<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Branches;
use Terminus\Collections\Workflows;
use Terminus\Models\Branch;
use Terminus\Models\Site;
use Terminus\Models\Workflow;

/**
 * Testing class for Pantheon\Terminus\Models\Branch
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
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->workflows = $this->workflows;
        $this->collection = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->site = $this->site;
        $this->model = new Branch((object)['id' => 'branch_id', 'sha' => 'sha',], ['collection' => $this->collection,]);
        $this->model->setRequest($this->request);
    }

    /**
     * Tests Branch::delete()
     */
    public function testDelete()
    {
        $this->site->id = 'site_id';

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
