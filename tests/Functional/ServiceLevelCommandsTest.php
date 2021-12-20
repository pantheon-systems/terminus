<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class ServiceLevelCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ServiceLevelCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\ServiceLevel\SetCommand
     *
     * @group service-level
     * @group long
     */
    public function testServiceLevelSetCommand()
    {
        $this->terminus(sprintf('service-level:set %s %s', $this->getSiteName(), 'performance_small'));
    }
}
