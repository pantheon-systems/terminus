<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
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

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Import\CompleteCommand
     * @covers \Pantheon\Terminus\Commands\Import\DatabaseCommand
     * @covers \Pantheon\Terminus\Commands\Import\FilesCommand
     * @covers \Pantheon\Terminus\Commands\Import\SiteCommand
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
