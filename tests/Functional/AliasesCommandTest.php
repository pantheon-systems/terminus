<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Config\DefaultsConfig;

/**
 * Class AliasesCommandTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class AliasesCommandTest extends TerminusTestBase
{
    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->isSiteFrameworkDrupal()) {
            $this->markTestSkipped(
                'A Drupal-based test site is required to test Drush-related "drush:aliases" command.'
            );
        }
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\AliasesCommand
     *
     * @throws \Exception
     *
     * @group aliases
     * @group short
     */
    public function testGetAliases()
    {
        // Test printed Drush 8 alias of the test site.
        $command = sprintf('drush:aliases --only=%s  --print', $this->getSiteName());
        $alias_printed = $this->terminus($command);
        $this->assertIsString($alias_printed);
        $aliases_site_name_needle = sprintf('$aliases[\'%s.*\']', $this->getSiteName());
        $this->assertTrue(
            false !== strpos($alias_printed, $aliases_site_name_needle),
            sprintf('List of Drush aliases should contain alias for %s site', $this->getSiteName())
        );
        $aliases_uri_needle = sprintf('${env-name}-%s.pantheonsite.io', $this->getSiteName());
        $this->assertTrue(
            false !== strpos($alias_printed, $aliases_uri_needle),
            sprintf('"uri" value should match "${env-name}-%s.pantheonsite.io"', $this->getSiteName())
        );
        $aliases_remote_host_needle = sprintf('appserver.${env-name}.%s.drush.in', $this->getSiteId());
        $this->assertTrue(
            false !== strpos($alias_printed, $aliases_remote_host_needle),
            sprintf('"remote-host" value should match "appserver.${env-name}.%s.drush.in"', $this->getSiteId())
        );
        $aliases_remote_user_needle = sprintf('${env-name}.%s', $this->getSiteId());
        $this->assertTrue(
            false !== strpos($alias_printed, $aliases_remote_user_needle),
            sprintf('"remote-user" value should match "${env-name}.%s"', $this->getSiteId())
        );
        $this->assertTrue(
            false !== strpos($alias_printed, '-p 2222 -o "AddressFamily inet"'),
            '"ssh-options" value should match "-p 2222 -o "AddressFamily inet"'
        );
        $this->assertTrue(
            false !== strpos($alias_printed, '\'path-aliases\'')
            && false !== strpos($alias_printed, '\'%files\' => \'files\''),
            '"path-aliases" value should be present and match "[][\'%files\' => \'files\']"'
        );

        if (!$this->isCiEnv()) {
            // Prevent overriding drush aliases on a non-CI environment.
            return;
        }

        // Save all printed Drush 8 aliases to A variable.
        $aliases_printed = $this->terminus('drush:aliases --print');

        // Export Drush 8 and Drush 9 aliases.
        $this->terminus('drush:aliases');
        $config = new DefaultsConfig();
        $aliases_dir = $config->get('user_home') . '/.drush/';

        // Test Drush 8 aliases.
        $drush_8_aliases_in_file = file_get_contents($aliases_dir . 'pantheon.aliases.drushrc.php');
        $this->assertEquals($aliases_printed, trim($drush_8_aliases_in_file));

        // Get the first item from the list of available aliases.
        /** @var array $aliases */
        include $aliases_dir . 'pantheon.aliases.drushrc.php';
        $this->assertIsArray($aliases);
        $this->assertNotEmpty($aliases);
        $site_name = str_replace('.*', '', array_key_first($aliases));
        $site_alias = array_shift($aliases);

        // Test Drush 9 site alias.
        $drush_9_site_alias_file_path = $aliases_dir . 'sites/pantheon/' . $site_name . '.site.yml';
        $drush_9_site_alias_in_file = trim(file_get_contents($drush_9_site_alias_file_path));
        $expected_drush_9_site_alias = <<<EOF
'*':
  host: {$site_alias['remote-host']}
  paths:
    files: files
  uri: {$site_alias['uri']}
  user: {$site_alias['remote-user']}
  ssh:
    options: '-p 2222 -o "AddressFamily inet"'
    tty: false
EOF;
        $this->assertEquals($expected_drush_9_site_alias, $drush_9_site_alias_in_file);

        // Test --custom-domains flag.
        // Regenerate aliases for the same site whose alias file is read below
        // ($site_name, derived from the first alias above and used to build
        // $drush_9_site_alias_file_path). Using $this->getSiteName() here would
        // regenerate a potentially different site's file on multi-site accounts,
        // leaving the file we read with stale wildcard-only content.
        $this->terminus(sprintf('drush:aliases --only=%s --custom-domains', $site_name));

        // Re-read the Drush 9 site alias file to check for custom domain entries
        $drush_9_site_alias_with_custom_domains = trim(file_get_contents($drush_9_site_alias_file_path));

        // The output should now contain explicit entries for dev, test, and live environments
        // when custom domains are configured (and still contain the wildcard)
        $this->assertTrue(
            false !== strpos($drush_9_site_alias_with_custom_domains, "'dev':") ||
            false !== strpos($drush_9_site_alias_with_custom_domains, "'live':") ||
            false !== strpos($drush_9_site_alias_with_custom_domains, "'test':"),
            'Drush 9 alias file with --custom-domains should contain explicit environment entries (dev, test, or live)'
        );

        // Wildcard entry should still be present
        $this->assertTrue(
            false !== strpos($drush_9_site_alias_with_custom_domains, "'*':"),
            'Drush 9 alias file with --custom-domains should still contain wildcard entry'
        );
    }
}
