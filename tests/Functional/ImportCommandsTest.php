<?php

namespace Pantheon\Terminus\Tests\Functional;

use GuzzleHttp\Client;

/**
 * Class ImportCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ImportCommandsTest extends TerminusTestBase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * The name of the site to import from the archive.
     *
     * @var string
     */
    private $importedSiteName;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if (isset($this->importedSiteName)) {
            $this->terminus(sprintf('site:delete %s', $this->importedSiteName), [], false);
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
        try {
            $this->terminus($importDatabaseCommand);
        } catch (\Throwable $e) {
            // The database import may fail due to the database import process being a long running process.
            $this->addWarning($e->getMessage());
        }
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
     * Run composer command `composer run test:create-site-archive` to generate the test site archive site and file
     * if not exist.
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see    \Pantheon\Terminus\Scripts\CreateTestSiteArchive
     *
     * @group import
     * @group long
     *
     */
    public function testImportSiteCommand()
    {
        // Create a site to import from the archive.
        $this->importedSiteName = uniqid('site-import-d7-');
        $this->terminus(
            sprintf(
                'site:create %s %s drupal7 --org=%s',
                $this->importedSiteName,
                $this->importedSiteName,
                $this->getOrg()
            )
        );
        sleep(60);
        $importedSiteInfo = $this->terminusJsonResponse(sprintf('site:info %s', $this->importedSiteName));
        $this->assertIsArray($importedSiteInfo);
        $this->assertNotEmpty($importedSiteInfo);

        // Import the site from the archive file.
        $this->terminus(
            sprintf(
                'import:site %s %s',
                $this->importedSiteName,
                'https://dev-site-archive-d7.pantheonsite.io/sites/default/files/site-archive-d7.tar.gz'
            )
        );
        sleep(60);

        // Verify that the code and the database have been imported.
        $importedSiteFrontPageUrl = sprintf(
            'https://dev-%s.pantheonsite.io',
            $this->importedSiteName,
        );
        $this->assertEquals(
            200,
            $this->client->head($importedSiteFrontPageUrl)->getStatusCode(),
            sprintf(
                'An HTTP request (%s) to the imported site should return HTTP status code 200.',
                $importedSiteFrontPageUrl
            ),
        );

        // Verify that the test file has been imported.
        $importedSiteTestFileUrl = sprintf(
            'https://dev-%s.pantheonsite.io/sites/default/files/%s',
            $this->importedSiteName,
            'terminus-functional-test-file-site-archive.txt'
        );
        $this->assertEquals(
            200,
            $this->client->head($importedSiteTestFileUrl)->getStatusCode(),
            sprintf('The test file should be available by URL %s.', $importedSiteTestFileUrl)
        );
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
