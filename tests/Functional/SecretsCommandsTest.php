<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SecretsCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SecretsCommandsTest extends TerminusTestBase
{
    /**
     * @test
     *
     * @group secrets
     * @group short
     */
    public function testSecretsCommandsExist()
    {
        $this->assertCommandExists('secret:org:delete');
        $this->assertCommandExists('secret:org:list');
        $this->assertCommandExists('secret:org:set');
        $this->assertCommandExists('secret:site:delete');
        $this->assertCommandExists('secret:site:list');
        $this->assertCommandExists('secret:site:set');
        $this->assertCommandExists('secret:site:local-generate');
    }

    /**
     * @test
     *
     * @group secrets
     * @group short
     */
    public function testSecretSiteSetHasExpectedOptions()
    {
        $helpOutput = $this->terminus('help secret:site:set');
        $this->assertStringContainsString('--type', $helpOutput);
        $this->assertStringContainsString('--scope', $helpOutput);
        $this->assertStringContainsString('--debug', $helpOutput);
    }

    /**
     * @test
     *
     * @group secrets
     * @group short
     */
    public function testSecretOrgSetHasExpectedOptions()
    {
        $helpOutput = $this->terminus('help secret:org:set');
        $this->assertStringContainsString('--type', $helpOutput);
        $this->assertStringContainsString('--scope', $helpOutput);
        $this->assertStringContainsString('--env', $helpOutput);
        $this->assertStringContainsString('--debug', $helpOutput);
    }

    /**
     * @test
     *
     * @group secrets
     * @group long
     */
    public function testSecretSiteListCommand()
    {
        $siteName = $this->getSiteName();
        if (empty($siteName)) {
            $this->markTestSkipped('TERMINUS_SITE environment variable not set.');
        }

        $output = $this->terminus(
            sprintf('secret:site:list %s', $siteName)
        );
        $this->assertNotNull($output);
    }
}
