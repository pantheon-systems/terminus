<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class LocalCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class LocalCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @setup
     */
    public function setUp(): void
    {
        $sitename = $this->getSiteName();
        $local_sites_folder = realpath(getenv('TERMINUS_LOCAL_SITES')) . DIRECTORY_SEPARATOR .
            'pantheon-local-copies';
        $willBeCreated = $local_sites_folder . DIRECTORY_SEPARATOR . $sitename;
        if (is_dir($willBeCreated)) {
            exec("rm -Rf $willBeCreated");
        }
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\CloneCommand
     *
     * @group local
     * @group long
     */
    public function testLocalClone()
    {
        $sitename = $this->getSiteName();
        $result = $this->terminus("local:clone {$sitename}", null);
        if (!is_string($result)) {
            throw new \Exception("The response from the local clone command didn't return the path.");
        }
        $shouldExist = $result . DIRECTORY_SEPARATOR . '.git';
        $this->assertTrue(
            is_dir($shouldExist),
            "The sites .git directory does not exist: {$shouldExist}"
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
        $sitename = $this->getSiteName();
        $result = $this->terminus("local:getLiveDB {$sitename}.live");
        $this->assertTrue(
            is_file($result),
            "The db file failed to download."
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
        $sitename = $this->getSiteName();
        $result = $this->terminus("local:getLiveFiles {$sitename}.live");
        $this->assertTrue(
            is_file($result),
            'The site file failed to download.'
        );
    }

    /**
     * @after
     */
    public function tearDown(): void
    {
        $sitename = $this->getSiteName();
        $local_site_folder = realpath(getenv('TERMINUS_LOCAL_SITES')) . DIRECTORY_SEPARATOR .
            'pantheon-local-copies' . DIRECTORY_SEPARATOR . $sitename;
        if (is_dir($local_site_folder)) {
            exec("rm -Rf {$local_site_folder}");
        }
    }
}
