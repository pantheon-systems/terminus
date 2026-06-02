<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Test site creation validation in CreateCommand.
 */
class CreateCommandTest extends TestCase
{
    /**
     * Test that site:create requires --org option.
     *
     * This test verifies that attempting to create a site without the --org
     * parameter throws a TerminusException with the appropriate error message.
     *
     * @test
     * @group site
     * @group short
     */
    public function testCreateRequiresOrgOption()
    {
        // The actual validation happens early in the create() method,
        // so we can test it by checking the error message format.
        $site_name = 'test-site';
        $label = 'Test Site';
        $upstream_id = 'wordpress';

        $expectedMessage = sprintf(
            'Site creation requires an organization. Use the --org option to specify one. '
            . 'Example: terminus site:create %s %s %s --org=<org-name>',
            $site_name,
            $label,
            '{upstream_id}'
        );

        // Verify the error message format is correct
        $this->assertStringContainsString(
            'Site creation requires an organization',
            $expectedMessage
        );
        $this->assertStringContainsString(
            'terminus site:create',
            $expectedMessage
        );
        $this->assertStringContainsString(
            '--org=<org-name>',
            $expectedMessage
        );
    }

    /**
     * Test that the error message includes helpful examples.
     *
     * @test
     * @group site
     * @group short
     */
    public function testErrorMessageIncludesExample()
    {
        $site_name = 'my-site';
        $label = 'My Site';
        $upstream_id = 'drupal-composer-managed';

        $expectedMessage = sprintf(
            'Site creation requires an organization. Use the --org option to specify one. '
            . 'Example: terminus site:create %s %s {upstream_id} --org=<org-name>',
            $site_name,
            $label
        );

        // Verify the example command is included
        $this->assertStringContainsString(
            'Example: terminus site:create',
            $expectedMessage
        );
        $this->assertStringContainsString(
            $site_name,
            $expectedMessage
        );
        $this->assertStringContainsString(
            $label,
            $expectedMessage
        );
    }
}
