<?php

namespace Pantheon\Terminus\Tests\Functional;

use GuzzleHttp\Client;

/**
 * Class BackupCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class BackupCommandsTest extends TerminusTestBase
{
    /**
     * @var Client
     */
    protected $client;

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
    public function testBackupCreateListInfoGetCommand()
    {
        $this->terminus(sprintf('backup:create %s --element=database', $this->getSiteEnv()));
        $backupList = $this->terminusJsonResponse(
            sprintf('backup:list %s --element=database', $this->getSiteEnv()),
            false
        );
        $this->assertIsArray(
            $backupList,
            sprintf('A list of backups should be an array. %s', print_r($backupList, true))
        );
        $backup = array_shift($backupList);
        $this->assertArrayHasKey('file', $backup, 'A backup should have "file" field.');

        $backupInfo = $this->terminusJsonResponse(sprintf('backup:info %s --element=database', $this->getSiteEnv()));
        $this->assertIsArray($backupInfo);
        $this->assertArrayHasKey('file', $backupInfo, 'A backup info should have "file" field.');
        $this->assertArrayHasKey('url', $backupInfo, 'A backup info should have "url" field.');
        $statusCode = $this->client->head($backupInfo['url'])->getStatusCode();
        $this->assertEquals(200, $statusCode, sprintf('Can\'t find a backup file by URL %s.', $backupInfo['url']));

        $url = $this->terminus(sprintf('backup:get %s --element=database', $this->getSiteEnv()));
        $this->assertNotEmpty($url);
        $this->assertIsString($url);
        $statusCode = $this->client->head($url)->getStatusCode();
        $this->assertEquals(200, $statusCode, sprintf('Can\'t find a backup file by URL %s.', $url));
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
        $siteEnv = $this->getSiteEnv();

        $this->terminus(sprintf('backup:automatic:disable %s', $siteEnv));
        $this->assertTerminusCommandResultEqualsInAttempts(function () use ($siteEnv) {
            return $this->terminusJsonResponse(sprintf('backup:automatic:info %s', $siteEnv));
        }, [
            'daily_backup_hour' => null,
            'weekly_backup_day' => null,
            'expiry' => null,
        ]);

        $this->terminus(sprintf('backup:automatic:enable %s', $siteEnv));
        $this->assertTerminusCommandResultEqualsInAttempts(function () use ($siteEnv) {
            // Count non-empty elements in the result which is expected to be exactly 3
            // ("daily_backup_hour", "weekly_backup_day" and "expiry").
            return count(array_filter($this->terminusJsonResponse(sprintf('backup:automatic:info %s', $siteEnv))));
        }, 3);
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
        $this->terminus(sprintf('backup:create %s --element=database --keep-for=1', $this->getSiteEnv()));

        $latestBackupUrl = $this->terminus(sprintf('backup:get %s --element=database', $this->getSiteEnv()));
        $this->assertIsString($latestBackupUrl, 'A URL of a backup should be string.');
        $this->assertNotEmpty($latestBackupUrl, 'A URL of a backup should not be empty.');

        preg_match('/(\d+)_backup/', $latestBackupUrl, $matches);
        if (!isset($matches[1])) {
            $this->fail('A URL of backup should contain timestamp');
        }
        $latestBackupTimestamp = $matches[1];

        if ($latestBackupTimestamp < $startOfCommandExecutionTimestamp) {
            $this->fail('Command "backup:get" should return URL of the most recent backup.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client();
    }
}
