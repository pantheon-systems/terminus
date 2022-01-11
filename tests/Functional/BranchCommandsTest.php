<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class BranchCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class BranchCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Branch\ListCommand
     *
     * @group branch
     * @group short
     */
    public function testBranchList()
    {
        $sitename = $this->getSiteName();
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
