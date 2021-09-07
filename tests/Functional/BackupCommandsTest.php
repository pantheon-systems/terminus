<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class BackupCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class BackupCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use UrlStatusCodeHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Backup\GetCommand
     * @covers \Pantheon\Terminus\Commands\Backup\InfoCommand
     * @covers \Pantheon\Terminus\Commands\Backup\ListCommand
     * @covers \Pantheon\Terminus\Commands\Backup\CreateCommand
     *
     * @group backup
     * @group short
     */
    public function testCreateListInfoGetCommand()
    {
        $siteName = $this->getSiteName();
        $this->terminus("backup:create {$siteName}.live --element=database", null);
        $backupList = $this->terminusJsonResponse("backup:list {$siteName}.live --element=database");
        $this->assertIsArray($backupList, "Backup list response should be an array");
        $backup = array_shift($backupList);
        $this->assertArrayHasKey('file', $backup, "backup from list should have file property");
        $backup = $this->terminusJsonResponse(
            "backup:info {$siteName}.live"
        );
        $this->assertIsArray(
            $backup,
            "Backup info response should be an array."
        );
        $this->assertArrayHasKey(
            "file",
            $backup,
            "Backup info response should have file property"
        );
        $this->assertArrayHasKey(
            "url",
            $backup,
            "Backup info response should be an array."
        );
        $statusCode = $this->getStatusCodeForUrl($backup['url']);
        $this->assertEquals(200, $statusCode, "Status Code from backup url should be 200");
        $url = $this->terminus("backup:get {$siteName}.live");
        $this->assertIsString($url, "Backup url should be a string.");
        $statusCode = $this->getStatusCodeForUrl($url);
        $this->assertEquals(200, $statusCode, "Status Code from backup url should be 200");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Backup\Automatic\InfoCommand
     * @covers \Pantheon\Terminus\Commands\Backup\Automatic\EnableCommand
     * @covers \Pantheon\Terminus\Commands\Backup\Automatic\DisableCommand
     *
     * @group backup
     * @group long
     */
    public function testAutomaticBackupInfoEnableDisable()
    {
        $siteName = $this->getSiteName();
        $auto = $this->terminusJsonResponse("backup:automatic:info {$siteName}.live");
        $this->assertIsArray(
            $auto,
            "returned auto database backup call should return an array"
        );
        $this->assertArrayHasKey(
            'daily_backup_hour',
            $auto,
            'Backup info response should have file property'
        );
        $this->assertArrayHasKey(
            'expiry',
            $auto,
            'Backup info response should have file property'
        );
        $newValue = $auto['weekly_backup_day'] === null ? "enable" : "disable";
        $this->terminus("backup:automatic:{$newValue} {$siteName}.live", null);
        sleep(20);
        $auto2 = $this->terminusJsonResponse("backup:automatic:info {$siteName}.live");
        $newValue2 = $auto2['weekly_backup_day'] === null ? 'enable' : 'disable';
        $this->assertNotEquals($newValue, $newValue2);
        $this->terminus("backup:automatic:{$newValue2} {$siteName}.live", null);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Backup\GetCommand
     *
     * @group backup
     * @group short
     */
    public function testBackupGetLatest()
    {
        $startOfCommandExecutionTimestamp = time();
        $this->terminus("backup:create {$this->getSiteName()}.live --element=database --keep-for=1", null);

        $latestBackupUrl = $this->terminus("backup:get {$this->getSiteName()}.live --element=database");
        $this->assertIsString($latestBackupUrl, 'A URL of a backup should be string');
        $this->assertNotEmpty($latestBackupUrl, 'A URL of a backup should not be empty');

        preg_match('/(\d+)_backup/', $latestBackupUrl, $matches);
        if (!isset($matches[1])) {
            $this->fail('A URL of backup should contain timestamp');
        }
        $latestBackupTimestamp = $matches[1];

        if ($latestBackupTimestamp < $startOfCommandExecutionTimestamp) {
            $this->fail('Command "backup:get" should return URL of the most recent backup');
        }
    }
}
