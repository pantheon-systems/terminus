<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class TerminusModelTest
 * Testing class for Pantheon\Terminus\Models\TerminusModel
 * @package Pantheon\Terminus\UnitTests\Models
 */
class TerminusModelTest extends ModelTestCase
{
    /**
     * Tests TerminusModel::__construct(), ::get(), ::has(), and ::set()
     */
    public function testConstructGetHasSet()
    {
        $model = $this->getMockForAbstractClass(TerminusModel::class, [(object)['id' => '123', 'foo' => 'bar']]);

        $this->assertEquals('123', $model->id);
        $this->assertTrue($model->has('foo'));
        $this->assertEquals('bar', $model->get('foo'));
        $this->assertFalse($model->has('baz'));
        $model->set('baz', 'abc');
        $this->assertTrue($model->has('baz'));
        $this->assertEquals('abc', $model->get('baz'));
        $this->assertEquals('bar', $model->get('foo'));
    }

    /**
     * Tests TerminusModel::getDatetime()
     */
    public function testGetDatetime()
    {
        $happy_kirkday = '2233-03-22';
        $model_data = (object)[
            'id' => '123',
            'missing_datetime' => null,
            'textual_datetime' => 'March 22, 2233',
            'unix_datetime' => 8306409600,
        ];
        $model = $this->getMockForAbstractClass(TerminusModel::class, [$model_data,]);

        $this->config->expects($this->exactly(2))
            ->method('formatDatetime')
            ->with($model_data->unix_datetime)
            ->willReturn($happy_kirkday);
        $model->setConfig($this->config);

        $this->assertNull($model->getDatetime('missing_datetime'));
        $this->assertEquals($happy_kirkday, $model->getDatetime('textual_datetime'));
        $this->assertEquals($happy_kirkday, $model->getDatetime('unix_datetime'));
    }

    /**
     * Tests TerminusModel::fetch()
     */
    public function testFetch()
    {
        $model = $this->getMockBuilder(TerminusModel::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $model->expects($this->once())
            ->method('getUrl')
            ->willReturn('TESTURL');
        
        $this->request->expects($this->once())
            ->method('request')
            ->with('TESTURL', ['options' => ['method' => 'get',], 'foo' => 'bar'])
            ->willReturn(['data' => ['baz' => '123']]);

        $model->setRequest($this->request);

        $model->fetch(['foo' => 'bar']);
    }
}
