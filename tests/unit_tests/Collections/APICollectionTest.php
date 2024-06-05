<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\APICollection;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class APICollectionTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class APICollectionTest extends CollectionTestCase
{
    /**
     * Tests APICollection::fetch()
     */
    public function testFetch()
    {
        $data = [
            'a' => (object)['id' => 'a', 'foo' => '123', 'category' => 'a',],
            'b' => (object)['id' => 'b', 'foo' => '456', 'category' => 'a',],
            'c' => (object)['id' => 'c', 'foo' => '678', 'category' => 'b',],
            'd' => (object)['id' => 'd', 'foo' => ['key' => 'value',], 'category' => 'b',],
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with('TESTURL', ['options' => ['method' => 'get',],])
            ->willReturn(['data' => $data]);

        $collection = $this->getMockBuilder(APICollection::class)
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
    }

    /**
     * Tests APICollection::setPaging(bool) and APICollection::isPaged()
     */
    public function testPaging()
    {
        $collection = $this->getMockForAbstractClass(APICollection::class);

        $this->assertEquals($collection, $collection->setPaging(false));
        $this->assertTrue($collection->setPaging(true)->isPaged());
        $this->assertFalse($collection->setPaging(false)->isPaged());
    }
}
