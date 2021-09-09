<?php

namespace Pantheon\Terminus\Tests\Functional;

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
     * @group import
     * @group todo
     */
    public function testImportSite()
    {
        $this->fail('Not implemented. Requirement: a Drupal-based test site to create a site archive via Drush.');
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
