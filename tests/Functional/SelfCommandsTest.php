<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SelfCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SelfCommandsTest extends TerminusTestBase
{
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
