<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class SSHKeyCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SSHKeyCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\SSHKey\ListCommand
     * @covers \Pantheon\Terminus\Commands\SSHKey\AddCommand
     * @covers \Pantheon\Terminus\Commands\SSHKey\RemoveCommand
     *
     * @group sshkey
     * @group todo
     */
    public function testSSHKeyCommand()
    {
        $this->fail("To Be Written.");
    }
}
