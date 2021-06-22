<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
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
    use SiteBaseSetupTrait;
    use UrlStatusCodeHelperTrait;
    use LoginHelperTrait;

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
        $siteName = getenv('TERMINUS_SITE');
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
}
