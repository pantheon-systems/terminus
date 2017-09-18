<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\WorkflowOperations;
use Pantheon\Terminus\Models\WorkflowOperation;

/**
 * Class FetchlessCollectionTest
 * Testing class for Pantheon\Terminus\Collections\FetchlessCollection
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class FetchlessCollectionTest extends CollectionTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->data = [
            (object)['id' => 'op-id-1', 'type' => 'type', 'description' => 'an operation', 'result' => 'succeeded', 'duration' => 100,],
            (object)['id' => 'op-id-2', 'type' => 'type2', 'description' => 'another operation', 'result' => null, 'duration' => 0,],
            (object)['id' => 'op-id-3', 'type' => 'type', 'description' => 'more operation', 'result' => 'failed', 'duration' => 300,],
        ];
    }

    /**
     * Tests the FetchlessCollection functions
     */
    public function testCollection()
    {
        $operations = new WorkflowOperations(['data' => $this->data,]);
        $operations->setContainer($this->container);

        for ($i = 0; $i < count($this->data); $i++) {
            $data = $this->data[$i];
            $op = $this->getMockBuilder(WorkflowOperation::class)
                ->enableOriginalConstructor()
                ->setConstructorArgs(compact('data'))
                ->getMock();
            $this->container->expects($this->at($i))
                ->method('get')
                ->with(WorkflowOperation::class, [$data, ['id' => $data->id, 'collection' => $operations,],])
                ->willReturn($op);
        }

        $this->assertEquals($this->data, $operations->getData());

        $ops = $operations->fetch()->all();
        foreach ($ops as $op) {
            $this->assertInstanceOf(WorkflowOperation::class, $op);
        }

        $operations->setData([]);
        $this->assertEmpty($operations->getData());
    }
}
