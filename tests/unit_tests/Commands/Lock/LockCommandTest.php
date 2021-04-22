<?php

namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Pantheon\Terminus\UnitTests\Commands\Env\EnvCommandTest;
use Pantheon\Terminus\Models\Lock;

/**
 * Class LockCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\Lock
 */
abstract class LockCommandTest extends EnvCommandTest
{
    /**
     * @var Lock
     */
    protected $lock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->lock = $this->getMockBuilder(Lock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->id = 'env_id';
        $this->environment->expects($this->once())
            ->method('getLock')
            ->with()
            ->willReturn($this->lock);
    }
}
