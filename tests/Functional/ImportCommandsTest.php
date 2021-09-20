<?php

namespace Pantheon\Terminus\Tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ImportCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ImportCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $mockSiteName;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->client = new Client();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if (isset($this->mockSiteName)) {
            $this->terminus(sprintf('site:delete %s', $this->mockSiteName), [], false);
        }
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Import\DatabaseCommand
     *
     * @group import
     * @group short
     */
    public function testImportDatabase()
    {
        $backupUrl = $this->getBackupUrl('database');

        $importDatabaseCommand = sprintf('import:database %s "%s"', $this->getSiteEnv(), $backupUrl);
        $this->terminus($importDatabaseCommand);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Import\FilesCommand
     *
     * @group import
     * @group short
     */
    public function testImportFiles()
    {
        $backupUrl = $this->getBackupUrl('files');

        $importFilesCommand = sprintf('import:files %s "%s"', $this->getSiteEnv(), $backupUrl);
        $this->terminus($importFilesCommand);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Import\SiteCommand
     *
     * Uses a preconfigured minimalist Drupal7 site archive uploaded as a file artifact into the test site (to be publicly
     * available as https://dev-[test-site-name].pantheonsite.io/sites/default/files/site-import-d7-mock-archive.tar.gz).
     * The original archive file is located at /tests/fixtures/functional/site-import-d7-mock-archive.tar.gz
     * There are the following expectations from an imported site:
     * - "node/1" page should be available;
     * - an image sites/default/files/styles/large/public/field/image/image_2021-08-25_11-32-29.png should be available.
     *
     * @group import
     * @group long
     *
     * @throws \Exception
     */
    public function testImportSiteCommand()
    {
        $this->mockSiteName = uniqid('site-import-d7-mock-');
        $this->terminus(
            sprintf(
                'site:create %s %s drupal7 --org=%s',
                $this->mockSiteName,
                $this->mockSiteName,
                $this->getOrg()
            )
        );
        sleep(60);

        $siteInfo = $this->terminusJsonResponse(sprintf('site:info %s', $this->mockSiteName));
        $this->assertIsArray($siteInfo);
        $this->assertNotEmpty($siteInfo);

        $siteArchiveUrl = sprintf(
            'https://%s-%s.pantheonsite.io/sites/default/files/site-import-d7-mock-archive.tar.gz',
            $this->getMdEnv(),
            $this->getSiteName(),
        );
        try {
            $this->client->head($siteArchiveUrl);
        } catch (GuzzleException $e) {
            $this->fail(
                sprintf(
                    'The site archive file (%s) should be publicly available. Error: %s',
                    $siteArchiveUrl,
                    $e->getMessage()
                )
            );
        }

        $this->terminus(sprintf('import:site %s %s', $this->mockSiteName, $siteArchiveUrl));

        $testPagePath = 'node/1';
        $mockSitePageUrl = sprintf('https://dev-%s.pantheonsite.io/%s', $this->mockSiteName, $testPagePath);
        try {
            $this->client->head($mockSitePageUrl);
        } catch (GuzzleException $e) {
            $this->fail(
                sprintf(
                    'Test page "%s" should be accessible on mock site (%s) once the site archive imported. Error: %s',
                    $testPagePath,
                    $this->mockSiteName,
                    $e->getMessage()
                )
            );
        }

        $testImagePath = 'sites/default/files/styles/large/public/field/image/image_2021-08-25_11-32-29.png';
        $mockSiteImageUrl = sprintf('https://dev-%s.pantheonsite.io/%s', $this->mockSiteName, $testImagePath);
        try {
            $this->client->head($mockSiteImageUrl);
        } catch (GuzzleException $e) {
            $this->fail(
                sprintf(
                    'Test image (%s) should be accessible on mock site (%s) once the site archive imported. Error: %s',
                    $testImagePath,
                    $this->mockSiteName,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Import\CompleteCommand
     *
     * @group import
     * @group short
     */
    public function testImportComplete()
    {
        $this->terminus(sprintf('import:complete %s', $this->getSiteName()));
    }

    /**
     * Creates backup and returns backup URL.
     *
     * @param string $element
     *   The type of the backup (element).
     *
     * @return string
     *   The backup URL.
     */
    private function getBackupUrl(string $element): string
    {
        $backupCreateCommand = sprintf('backup:create %s --element=%s --keep-for=1', $this->getSiteEnv(), $element);
        $this->terminus($backupCreateCommand);

        $backupListCommand = sprintf('backup:list %s --element=%s', $this->getSiteEnv(), $element);
        $listOfBackups = $this->terminusJsonResponse($backupListCommand);
        $this->assertIsArray($listOfBackups, 'List of backups should be an array');
        $latestBackup = array_shift($listOfBackups);
        $this->assertArrayHasKey(
            'file',
            $latestBackup,
            'An item from the list of backups should have "file" property'
        );

        $backupInfoCommand = sprintf('backup:get %s --file=%s', $this->getSiteEnv(), $latestBackup['file']);
        $latestBackupUrl = $this->terminus($backupInfoCommand);
        $this->assertIsString($latestBackupUrl, 'A URL of a backup should be string');
        $this->assertNotEmpty($latestBackupUrl, 'A URL of a backup should not be empty');

        return $latestBackupUrl;
    }
}
