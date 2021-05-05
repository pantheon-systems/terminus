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
     * @testdox Tests APICollection::fetch()
     * @test
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
            ->with('TESTURL', ['options' => ['method' => 'get']])
            ->willReturn(['data' => $data]);
        $collection = $this->getMockBuilder(APICollection::class)
            ->onlyMethods([
                'getUrl',
                'request',
                'getData'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $collection->expects($this->once())
            ->method('getUrl')
            ->willReturn('TESTURL');
        $collection->expects($this->once())
            ->method('getData')
            ->willReturn($data);


        $models = [];
        $options = ['collection' => $collection,];
        $i = 1;
        foreach ($data as $key => $model_data) {
            $models[$model_data->id] = $this->getMockForAbstractClass(
                TerminusModel::class,
                [$model_data, $options],
                '',
                true,
                false,
                true,
                ['serialize', 'fetch']
            );
            $options['id'] = $model_data->id;
            $this->container->expects($this->exactly(3))
                ->method('get')
                ->withConsecutive(TerminusModel::class, [$model_data, $options])
                ->willReturn($this->onConsecutiveCalls(
                    $this->returnValue('serialize'),
                    $this->returnValue('fetch')
                ));
            $models[$model_data->id]->method('serialize')->willReturn($model_data);
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
     * @testdox Tests APICollection::setPaging(bool) and APICollection::isPaged()
     * @test
     */
    public function testPaging()
    {
        $collection = $this->getMockForAbstractClass(APICollection::class);

        $this->assertEquals($collection, $collection->setPaging(false));
        $this->assertTrue($collection->setPaging(true)->isPaged());
        $this->assertFalse($collection->setPaging(false)->isPaged());
    }
}
