<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class SSHKeyCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SSHKeyCommandsTest extends TestCase
{
    use TerminusTestTrait;

    private const TEST_SSH_KEY_DESCRIPTION = 'DevUser@pantheon.io';

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
        $keysListBefore = $this->terminusJsonResponse('ssh-key:list');

        // Search for the test key and remove if exists.
        $testKeyId = $this->getSshKeyIdByDescription($keysListBefore, self::TEST_SSH_KEY_DESCRIPTION);
        if (null !== $testKeyId) {
            $this->terminus(sprintf('ssh-key:remove %s', $testKeyId));
            // Update the initial list of keys.
            $keysListBefore = $this->terminusJsonResponse('ssh-key:list');
        }

        // Add the test key.
        $testKeyPath = sprintf('%s/tests/config/dummy_key.pub', getcwd());
        $this->terminus(sprintf('ssh-key:add %s', $testKeyPath));
        $keysListAfter = $this->terminusJsonResponse('ssh-key:list');
        $this->assertEquals(count($keysListBefore) + 1, count($keysListAfter));
        $testKeyId = $this->getSshKeyIdByDescription($keysListAfter, self::TEST_SSH_KEY_DESCRIPTION);
        $this->assertNotNull($testKeyId);

        // Remove the test key.
        $this->terminus(sprintf('ssh-key:remove %s', $testKeyId));
        $keysListAfter = $this->terminusJsonResponse('ssh-key:list');
        $this->assertEquals(count($keysListBefore), count($keysListAfter));
    }

    /**
     * Returns the SSH key ID by description.
     *
     * @param array $keys
     * @param string $description
     *
     * @return string|null
     */
    private function getSshKeyIdByDescription(array $keys, string $description): ?string
    {
        $keysListIdToDescription = array_map(fn ($value) => $value['comment'], $keys);

        return array_search($description, $keysListIdToDescription) ?: null;
    }
}
