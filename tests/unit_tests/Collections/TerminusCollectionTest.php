<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Collections\WorkflowOperations;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\PaymentMethod;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Models\WorkflowOperation;

/**
 * Class TerminusCollectionTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class TerminusCollectionTest extends CollectionTestCase
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
     * Tests the TerminusCollection::add(object) function
     */
    public function testAdd()
    {
        $collection = $this->getMockForAbstractClass(TerminusCollection::class);

        $model_data = (object)[
            'id' => '123',
            'foo' => 'bar',
        ];
        $options = [
            'id' => '123',
            'collection' => $collection,
            'baz' => 'boo',
        ];
        $model = $this->getMockForAbstractClass(TerminusModel::class, [$model_data, $options,]);

        $this->container->expects($this->once())
            ->method('get')
            ->with(TerminusModel::class, [$model_data, $options,])
            ->willReturn($model);

        $collection->setContainer($this->container);
        $out = $collection->add($model_data, ['baz' => 'boo',]);
        $this->assertEquals($model, $out);
    }

    /**
     * Tests the TerminusCollection data functions
     */
    public function testDataFunctions()
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

    /**
     * Tests the TerminusCollection::get(string) function
     */
    public function testGet()
    {
        $model_data = [
            'id1' => (object)[
                'id' => 'id1',
                'foo' => 'bar',
            ],
            'id2' => (object)[
                'id' => 'id2',
                'foo' => 'bar2',
            ],
            'id3' => (object)[
                'id' => 'id3',
                'foo' => 'bar3',
            ],
        ];
        $url = 'a url';
        $collection = new DummyCollection(
            [
                'url' => $url,
                'collected_class' => PaymentMethod::class,
                'data' => $model_data,
            ]
        );
        $collection->setContainer($this->container);
        $collection->setRequest($this->request);

        $i = 0;
        foreach ($model_data as $data) {
            $params = ['id' => $data->id, 'collection' => $collection,];
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(
                    $this->equalTo(PaymentMethod::class),
                    $this->equalTo([$data, $params,])
                )
                ->willReturn(new PaymentMethod($data, $params));
        }

        $out = $collection->get('id1');
        $this->assertInstanceOf(PaymentMethod::class, $out);
        $this->assertEquals($model_data['id1']->foo, $out->get('foo'));

        $this->setExpectedException(
            TerminusNotFoundException::class,
            'Could not find a ' . PaymentMethod::$pretty_name . ' identified by invalid.'
        );
        $out = $collection->get('invalid');
        $this->assertNull($out);
    }
}
