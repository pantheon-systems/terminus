<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ImportCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ImportCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    // todo: cover \Pantheon\Terminus\Commands\Import\FilesCommand
    // todo: cover \Pantheon\Terminus\Commands\Import\SiteCommand
    // todo: cover \Pantheon\Terminus\Commands\Import\CompleteCommand

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Import\DatabaseCommand
     *
     * @group import
     * @group short
     */
    public function testImportDatabase()
    {
        $backupCreateCommand = sprintf(
            'backup:create %s.%s --element=database --keep-for=1',
            $this->getSiteName(),
            'live'
        );
        $this->terminus($backupCreateCommand);

        $backupListCommand = sprintf('backup:list %s.%s --element=database', $this->getSiteName(), 'live');
        $listOfDatabaseBackups = $this->terminusJsonResponse($backupListCommand);
        $this->assertIsArray($listOfDatabaseBackups, 'List of database backups should be an array');
        $latestDatabaseBackup = array_shift($listOfDatabaseBackups);
        $this->assertArrayHasKey(
            'file',
            $latestDatabaseBackup,
            'An item from the list of database backups should have "file" property'
        );

        $backupInfoCommand = sprintf(
            'backup:get %s.%s --file=%s',
            $this->getSiteName(),
            'live',
            $latestDatabaseBackup['file']
        );
        $latestDatabaseBackupUrl = $this->terminus($backupInfoCommand);
        $this->assertIsString($latestDatabaseBackupUrl, 'A URL of a backup should be string');
        $this->assertNotEmpty($latestDatabaseBackupUrl, 'A URL of a backup should be empty');

        $importDatabaseCommand = sprintf(
            'import:database --yes %s.%s "%s"',
            $this->getSiteName(),
            'live',
            $latestDatabaseBackupUrl
        );
        $this->terminus($importDatabaseCommand);
    }
}
