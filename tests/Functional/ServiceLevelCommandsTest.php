<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ServiceLevelCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ServiceLevelCommandsTest extends TestCase
{
    use TerminusTestTrait;

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
