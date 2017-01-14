<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Lock;

/**
 * Class LockTest
 * Testing class for Pantheon\Terminus\Models\Lock
 * @package Pantheon\Terminus\UnitTests\Models
 */
class LockTest extends ModelTestCase
{
    protected $workflow;
    protected $lock;
    protected $workflows;
    protected $environment;

    public function setUp()
    {
        parent::setUp();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lock = $this->_getLock(['locked' => false]);
    }

    protected function _getLock($attr)
    {
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getWorkflows')->willReturn($this->workflows);

        $lock = new Lock((object)$attr, ['environment' => $this->environment]);
        return $lock;
    }

    public function testEnable()
    {
        $params = ['username' => 'dev', 'password' => '123'];
        $this->workflows->expects($this->once())
            ->method('create')
            ->with('lock_environment', ['params' => $params])
            ->willReturn($this->workflow);

        $wf = $this->lock->enable($params);
        $this->assertEquals($this->workflow, $wf);
    }

    public function testIsLocked()
    {
        $this->assertFalse($this->lock->isLocked());

        $lock = $this->_getLock(['locked' => true, 'username' => 'abc', 'password' => '123']);
        $this->assertTrue($lock->isLocked());
    }

    public function testSerialize()
    {
        $expected = [
            'locked' => 'false',
            'username' => null,
            'password' =>  null,
        ];
        $actual = $this->lock->serialize();
        $this->assertEquals($expected, $actual);


        $lock = $this->_getLock(['locked' => true, 'username' => 'abc', 'password' => '123']);
        $actual = $lock->serialize();
        $expected = [
            'locked' => 'true',
            'username' => 'abc',
            'password' =>  '123',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testDisable()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with('unlock_environment')
            ->willReturn($this->workflow);

        $wf = $this->lock->disable();
        $this->assertEquals($this->workflow, $wf);
    }
}
