<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class SSHKeyCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SSHKeyCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\SSHKey\ListCommand
     * @covers \Pantheon\Terminus\Commands\SSHKey\AddCommand
     * @covers \Pantheon\Terminus\Commands\SSHKey\RemoveCommand
     *
     * @group sshkey
     * @group long
     */
    public function testSSHKeyCommand()
    {
        $this->fail("To Be Written.");
    }
}
