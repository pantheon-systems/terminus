<?php

namespace Pantheon\Terminus\Scripts;

// Set the "TERMINUS_TESTING_RUNTIME_ENV" env var to skip creating a runtime test multidev environment.
putenv('TERMINUS_TESTING_RUNTIME_ENV=dev');
require_once 'tests/config/bootstrap.php';

use Pantheon\Terminus\Tests\Functional\TerminusTestBase;

/**
 * Class CreateTestSiteArchive.
 *
 * @see \Pantheon\Terminus\Tests\Functional\ImportCommandsTest::testImportSiteCommand()
 */
class CreateTestSiteArchive extends TerminusTestBase
{
    private const SITE_NAME = 'site-archive-d7';
    private const TEST_FILE_NAME = 'terminus-functional-test-file-site-archive.txt';
    private const SITE_ARCHIVE_FILE_NAME = 'site-archive-d7.tar.gz';

    /**
     * Creates the test Drupal7 site containing a site archive file.
     *
     * The site archive URL: https://dev-site-archive-d7.pantheonsite.io/sites/default/files/site-archive-d7.tar.gz
     * Run composer command `composer test:create-site-archive` to generate the test site archive site and the file.
     *
     * @throws \JsonException
     */
    public static function do()
    {
        // Create the site.
        self::callTerminus(
            sprintf('site:create %s %s drupal7 --org=%s', self::SITE_NAME, self::SITE_NAME, self::getOrg())
        );
        sleep(60);

        // Install the Drupal7.
        self::callTerminus(sprintf('drush %s.dev -- site-install pantheon -y', self::SITE_NAME));

        // Upload a test file to the site.
        [$siteInfo] = self::callTerminus(
            sprintf('connection:info %s.dev --fields=sftp_username,sftp_host --format=json', self::SITE_NAME)
        );
        $siteInfo = json_decode($siteInfo, true, 512, JSON_THROW_ON_ERROR);
        $session = ssh2_connect($siteInfo['sftp_host'], 2222);
        ssh2_auth_agent($session, $siteInfo['sftp_username']);
        $sftp = ssh2_sftp($session);
        $stream = fopen(
            sprintf('ssh2.sftp://%d/files/%s', intval($sftp), self::TEST_FILE_NAME),
            'w'
        );
        fwrite($stream, 'This is a test file to use in Terminus functional testing assertions.');
        fclose($stream);

        // Create the site archive file.
        self::callTerminus(
            sprintf(
                'drush %s.dev -- archive-dump --destination=/files/%s',
                self::SITE_NAME,
                self::SITE_ARCHIVE_FILE_NAME
            ),
        );
        $siteArchiveFileUrl = sprintf(
            'https://dev-%s.pantheonsite.io/sites/default/files/%s',
            self::SITE_NAME,
            self::SITE_ARCHIVE_FILE_NAME,
        );

        print $siteArchiveFileUrl;
    }
}
