<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Config\DefaultsConfig;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class AliasesCommandTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class AliasesCommandTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
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
        $command = sprintf('drush:aliases --only=%s  --print', $this->getSiteName());
        $aliases = $this->terminus($command);
        $this->assertIsString($aliases);

        $aliases_site_name_needle = sprintf('$aliases[\'%s.*\']', $this->getSiteName());
        $this->assertTrue(
            false !== strpos($aliases, $aliases_site_name_needle),
            sprintf('List of Drush aliases should contain alias for %s site', $this->getSiteName())
        );

        $aliases_uri_needle = sprintf('${env-name}-%s.pantheonsite.io', $this->getSiteName());
        $this->assertTrue(
            false !== strpos($aliases, $aliases_uri_needle),
            sprintf('"uri" value should match "${env-name}-%s.pantheonsite.io"', $this->getSiteName())
        );

        $aliases_remote_host_needle = sprintf('appserver.${env-name}.%s.drush.in', $this->getSiteId());
        $this->assertTrue(
            false !== strpos($aliases, $aliases_remote_host_needle),
            sprintf('"remote-host" value should match "appserver.${env-name}.%s.drush.in"', $this->getSiteId())
        );

        $aliases_remote_user_needle = sprintf('${env-name}.%s', $this->getSiteId());
        $this->assertTrue(
            false !== strpos($aliases, $aliases_remote_user_needle),
            sprintf('"remote-user" value should match "${env-name}.%s"', $this->getSiteId())
        );

        $this->assertTrue(
            false !== strpos($aliases, '-p 2222 -o "AddressFamily inet"'),
            '"ssh-options" value should match "-p 2222 -o "AddressFamily inet"'
        );

        $this->assertTrue(
            false !== strpos($aliases, '\'path-aliases\'') && false !== strpos($aliases, '\'%files\' => \'files\''),
            '"path-aliases" value should be present and match "[][\'%files\' => \'files\']"'
        );

        // Save all Drush 8 aliases to variable.
        $aliases = $this->terminus('drush:aliases --print');

        // Export Drush 8 and Drush 9 aliases.
        $this->terminus('drush:aliases');
        $config = new DefaultsConfig();
        $aliases_dir = $config->get('user_home') . '/.drush/';

        // Test Drush 8 aliases.
        $drush_8_aliases_in_file = file_get_contents($aliases_dir . 'pantheon.aliases.drushrc.php');
        $this->assertEquals($aliases, $drush_8_aliases_in_file);

        // Test Drush 9 aliases.
        $drush_9_site_alias_file_path = $aliases_dir . 'sites/pantheon/' . $this->getSiteName() . '.site.yml';
        $drush_9_site_alias_in_file = file_get_contents($drush_9_site_alias_file_path);
        $expected_drush_9_site_alias = <<<EOF
'*':
  host: appserver.\${env-name}.{$this->getSiteId()}.drush.in
  paths:
    files: files
  uri: \${env-name}-{$this->getSiteName()}.pantheonsite.io
  user: \${env-name}.{$this->getSiteId()}
  ssh:
    options: '-p 2222 -o "AddressFamily inet"'
    tty: false
EOF;
        $this->assertEquals($expected_drush_9_site_alias, $drush_9_site_alias_in_file);
    }
}
