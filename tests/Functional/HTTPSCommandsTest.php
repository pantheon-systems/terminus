<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class HTTPSCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class HTTPSCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\HTTPS\InfoCommand
     * @covers \Pantheon\Terminus\Commands\HTTPS\RemoveCommand
     * @covers \Pantheon\Terminus\Commands\HTTPS\SetCommand
     *
     * @group https
     * @group todo
     */
    public function testHTTPS()
    {
        $this->fail("To Be Written");
    }
}
