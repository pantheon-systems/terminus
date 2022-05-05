<?php

namespace Pantheon\Terminus\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CustomerSecretsCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class CustomerSecretsCommandsTest extends TerminusTestBase
{
    protected const SECRET_NAME = 'foosecret';
    protected const SECRET_VALUE = 'secretbar';

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\CustomerSecrets\SetCommand
     * @covers \Pantheon\Terminus\Commands\CustomerSecrets\ListCommand
     * @covers \Pantheon\Terminus\Commands\CustomerSecrets\DeleteCommand
     *
     * @group customer-secrets
     * @group short
     */
    public function testCustomerSecretsCommands()
    {

        $this->assertCommandExists('customer-secrets:list');
        $this->assertCommandExists('customer-secrets:set');
        $this->assertCommandExists('customer-secrets:delete');

        // Set secret.
        $this->terminus(sprintf('customer-secrets:set %s %s %s', $this->getSiteName(), self::SECRET_NAME, self::SECRET_VALUE));

        // List secrets.
        $secretsList = $this->terminusJsonResponse(sprintf('customer-secrets:list %s', $this->getSiteName()));
        $this->assertIsArray($secretsList);
        $this->assertNotEmpty($secretsList);
        $secretFound = false;
        foreach ($secretsList as $secret) {
            if ($secret['name'] == self::SECRET_NAME) {
                $secretFound = true;
                break;
            }
        }
        $this->assertTrue($secretFound, 'Secret not found in list.');

        // Delete secret.
        $this->terminus(sprintf('customer-secrets:delete %s %s', $this->getSiteName(), self::SECRET_NAME));

        // List secrets again.
        $secretsList = $this->terminusJsonResponse(sprintf('customer-secrets:list %s', $this->getSiteName()));
        $this->assertIsArray($secretsList);
        $secretFound = false;
        foreach ($secretsList as $secret) {
            if ($secret['name'] == self::SECRET_NAME) {
                $secretFound = true;
                break;
            }
        }
        $this->assertFalse($secretFound, 'Secret found in list after it was deleted.');

    }
}
