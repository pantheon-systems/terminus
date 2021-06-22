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
     * @test
     * @covers \Pantheon\Terminus\Commands\Local\CloneCommand
     * @covers \Pantheon\Terminus\Commands\Local\CommitAndPushCommand
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveFilesCommand
     * @covers \Pantheon\Terminus\Commands\Local\GetLiveDBCommand
     *
     * @group branch
     * @gropu long
     */
    public function testBranchList()
    {
        $sitename = getenv('TERMINUS_SITE');
        $this->fail("To Be Written");
    }
}
