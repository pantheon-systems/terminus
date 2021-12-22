<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\TerminusUtilsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class SelfCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SelfCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use TerminusUtilsTrait;

    protected const SELF_UPDATE_COMMAND = 'self:update';

    /**
     * @test
     *
     * @group self
     * @group short
     */
    public function testSelfUpdateCommand()
    {
        $this->assertCommandExists(self::SELF_UPDATE_COMMAND);
    }
}
