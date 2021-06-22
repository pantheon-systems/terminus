<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class BranchCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class BranchCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Branch\ListCommand
     *
     * @group branch
     * @gropu short
     */
    public function testBranchList()
    {
        $sitename = getenv('TERMINUS_SITE');
        $branches = $this->terminusJsonResponse("branch:list {$sitename}");
        $this->assertIsArray(
            $branches,
            "Returned data from branch list should be an array"
        );
        $branch = array_shift($branches);
        $this->assertArrayHasKey(
            "id",
            $branch,
            "Returned data from new-relic:info should have a state value"
        );
        $this->assertArrayHasKey(
            "sha",
            $branch,
            "Returned data from new-relic:info should have a state value"
        );
    }
}
