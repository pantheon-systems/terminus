<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class LocalCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class LocalCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @setup
     */
    public function setUp(): void
    {
        $sitename = getenv('TERMINUS_SITE');
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
     * @gropu long
     */
    public function testLocalClone()
    {
        $sitename = getenv('TERMINUS_SITE');
        $local_sites_folder = realpath(getenv('TERMINUS_LOCAL_SITES')) . DIRECTORY_SEPARATOR .
            'pantheon-local-copies';
        $willBeCreated = $local_sites_folder . DIRECTORY_SEPARATOR . $sitename;
        $this->terminus("local:clone {$sitename}", null);
        $this->assertTrue(is_dir($willBeCreated));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\CommitAndPushCommand
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveFilesCommand
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveDBCommand
     *
     * @group local
     * @gropu long
     */
    public function testCommitDbFiles()
    {
        $this->fail("To Be Written");
    }

    /**
     * @after
     */
    public function tearDown(): void
    {
        $local_sites_folder = realpath(getenv('TERMINUS_LOCAL_SITES')) . DIRECTORY_SEPARATOR .
            'pantheon-local-copies';
        if (is_dir($local_sites_folder)) {
            exec("rm -Rf {$local_sites_folder}");
        }
    }
}
