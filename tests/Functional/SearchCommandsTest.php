<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SearchCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SearchCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Search\EnableCommand
     *
     * @group search
     * @group long
     */
    public function testSearchEnableCommand()
    {
        $this->assertTerminusCommandSucceedsInAttempts(sprintf('search:enable %s', $this->getSiteName()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Search\DisableCommand
     *
     * @group search
     * @group long
     */
    public function testSearchDisableCommand()
    {
        $this->assertTerminusCommandSucceedsInAttempts(sprintf('search:disable %s', $this->getSiteName()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Search\EnableCommand
     *
     * @group search
     * @group long
     */
    public function testSearchEnableCommandWithSolrFlavor()
    {
        $this->assertTerminusCommandSucceedsInAttempts(
            sprintf('search:enable %s --flavor=solr', $this->getSiteName())
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Search\DisableCommand
     *
     * @group search
     * @group long
     */
    public function testSearchDisableCommandWithSolrFlavor()
    {
        $this->assertTerminusCommandSucceedsInAttempts(
            sprintf('search:disable %s --flavor=solr', $this->getSiteName())
        );
    }
}
