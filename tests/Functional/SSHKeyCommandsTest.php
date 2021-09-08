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
     * @group ssh
     * @group short
     */
    public function testSSHKeyCommand()
    {
        $cwd = getcwd();
        $dummy_key_file = "$cwd/tests/config/dummy_key.pub";

        // Initial list
        $ssh_key_list = $this->terminusJsonResponse('ssh-key:list');
        $original_id_list = array_keys($ssh_key_list);
        $key_count = count($ssh_key_list);

        // Add new key
        $this->terminus("ssh-key:add $dummy_key_file");
        $ssh_key_list_new = $this->terminusJsonResponse('ssh-key:list');

        $this->assertGreaterThan($key_count, count($ssh_key_list_new));
        $new_id_list = array_keys($ssh_key_list_new);
        $new_key = array_diff($new_id_list, $original_id_list);
        $new_key = $new_key[0];

        // Remove
        $this->terminus("ssh-key:remove $new_key");
        $ssh_key_list_new2 = $this->terminusJsonResponse('ssh-key:list');
        $this->assertEquals($key_count, count($ssh_key_list_new2));
    }
}
