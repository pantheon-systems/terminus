<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\PaymentMethod;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class TerminusCollectionTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class TerminusCollectionTest extends CollectionTestCase
{
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

    public function testFetch()
    {
        $data = [
            'a' => (object)['id' => 'a', 'foo' => '123', 'category' => 'a',],
            'b' => (object)['id' => 'b', 'foo' => '456', 'category' => 'a',],
            'c' => (object)['id' => 'c', 'foo' => '678', 'category' => 'b',],
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with('TESTURL', ['options' => ['method' => 'get',],])
            ->willReturn(['data' => $data]);

        $collection = $this->getMockBuilder(TerminusCollection::class)
            ->setMethods(['getUrl',])
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUrl')
            ->willReturn('TESTURL');

        $models = [];
        $options = ['collection' => $collection,];
        $i = 0;
        foreach ($data as $key => $model_data) {
            $models[$model_data->id] = $this->getMockForAbstractClass(TerminusModel::class, [$model_data, $options,]);
            $options['id'] = $model_data->id;
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(TerminusModel::class, [$model_data, $options,])
                ->willReturn($models[$key]);
            $models[$model_data->id]->method('serialize')->willReturn((array)$model_data);
        }

        $collection->setRequest($this->request);
        $collection->setContainer($this->container);

        $collection->fetch();

        $this->assertEquals(array_keys($models), $collection->ids());
        $this->assertEquals($models, $collection->all());
        foreach ($models as $id => $model) {
            $this->assertEquals($model, $collection->get($id));
        }

        $expected = array_map(function ($d) {
            return (array)$d;
        }, $data);
        $this->assertEquals($expected, $collection->serialize());

        $listing = [
            'a' => '123',
            'b' => '456',
            'c' => '678',
        ];
        $this->assertEquals($listing, $collection->listing('id', 'foo'));
    }

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
        $collection = new DummyCollection(['url' => $url, 'collected_class' => PaymentMethod::class,]);
        $collection->setContainer($this->container);
        $collection->setRequest($this->request);

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo($url),
                $this->equalTo(['options' => ['method' => 'get',],])
            )
            ->willReturn(['data' => $model_data,]);

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
