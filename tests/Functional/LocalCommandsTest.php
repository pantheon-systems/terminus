<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class LocalCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class LocalCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->cleanUpTestSiteDir();
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        $this->cleanUpTestSiteDir();
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\CloneCommand
     *
     * @group local
     * @group long_fixme
     */
    public function testLocalClone()
    {
        $this->markTestSkipped('Debugging');
        $localSiteDir = $this->terminus(sprintf('local:clone %s', $this->getSiteName()));
        $this->assertNotEmpty($localSiteDir);
        $this->assertIsString($localSiteDir);

        $localSiteGitDir = $localSiteDir . DIRECTORY_SEPARATOR . '.git';
        $this->assertTrue(
            is_dir($localSiteGitDir),
            sprintf('The test local site ".git" directory %s does not exist.', $localSiteGitDir)
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveDBCommand
     *
     * @group local
     * @group long
     */
    public function testCommitDb()
    {
        $siteDatabaseSnapshotArchive = $this->terminus(sprintf('local:getLiveDB %s --overwrite', $this->getSiteName()));
        $this->assertTrue(
            is_file($siteDatabaseSnapshotArchive),
            'The database snapshot archive file failed to download.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveFilesCommand
     *
     * @group local
     * @group long
     */
    public function testCommitFiles()
    {
        $siteFilesArchive = $this->terminus(sprintf('local:getLiveFiles %s --overwrite', $this->getSiteName()));
        $this->assertTrue(is_file($siteFilesArchive), 'The site files archive file failed to download.');
    }

    /**
     * Deletes the local copy of the test site if exists.
     */
    private function cleanUpTestSiteDir()
    {
        $localTestSiteDir = $this->getLocalTestSiteDir();
        if (is_dir($localTestSiteDir)) {
            exec(sprintf('rm -rf %s', $localTestSiteDir));
        }
    }
}
