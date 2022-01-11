<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class LocalCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class LocalCommandsTest extends TerminusTestBase
{
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
     * @group long
     */
    public function testLocalCloneCommand()
    {
        if ($this->isCiEnv()) {
            $gitInfo = $this->terminusJsonResponse(
                sprintf('connection:info %s.dev --fields=git_host,git_port', $this->getSiteName())
            );
            $this->assertIsArray($gitInfo);
            $this->assertNotEmpty($gitInfo);
            $this->assertArrayHasKey('git_host', $gitInfo);
            $this->assertArrayHasKey('git_port', $gitInfo);

            $addGitHostToKnownHostsCommand = sprintf(
                'ssh-keyscan -p %d %s >> ~/.ssh/known_hosts',
                $gitInfo['git_port'],
                $gitInfo['git_host']
            );
            exec($addGitHostToKnownHostsCommand);
        }

        $localSiteDir = $this->terminus(sprintf('local:clone %s', $this->getSiteName()));
        $this->assertNotEmpty($localSiteDir);
        $this->assertIsString($localSiteDir);

        $localSiteGitDir = $localSiteDir . DIRECTORY_SEPARATOR . '.git';
        $this->assertTrue(
            is_dir($localSiteGitDir),
            sprintf('The test local site ".git" directory %s does not exist.', $localSiteGitDir)
        );

        $this->cleanUpTestSiteDir();
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveDBCommand
     *
     * @group local
     * @group long
     */
    public function testLocalGetLiveDbCommand()
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
    public function testLocalGetLiveFilesCommand()
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

        $filesBackupDir = $this->getLocalTestSiteDir() . DIRECTORY_SEPARATOR . 'files';
        $filesBackupFile = sprintf(
            '%s%s%s-files.tgz',
            $filesBackupDir,
            DIRECTORY_SEPARATOR,
            $this->getSiteName()
        );
        if (is_file($filesBackupFile)) {
            exec(sprintf('rm %s', $filesBackupFile), $output, $code);
        }

        $dbBackupDir = $this->getLocalTestSiteDir() . DIRECTORY_SEPARATOR . 'db';
        $dbBackupFile = sprintf(
            '%s%s%s-files.tgz',
            $dbBackupDir,
            DIRECTORY_SEPARATOR,
            $this->getSiteName()
        );
        if (is_file($dbBackupFile)) {
            exec(sprintf('rm %s', $dbBackupFile));
        }
    }
}
