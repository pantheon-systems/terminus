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
