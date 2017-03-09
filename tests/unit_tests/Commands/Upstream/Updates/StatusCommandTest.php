<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Commands\Upstream\Updates\StatusCommand;

/**
 * Class StatusCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\Updates\StatusCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream\Updates
 */
class StatusCommandTest extends UpdatesCommandTest
{
    /**
     * @var Commits
     */
    protected $commits;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->commits = $this->getMockBuilder(Commits::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->expects($this->once())
            ->method('getCommits')
            ->with()
            ->willReturn($this->commits);

        $this->command = new StatusCommand($this->getConfig());
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the upstream:updates:status command when the environment is current
     */
    public function testStatusCurrent()
    {
        $this->commits->expects($this->once())
            ->method('getReadyToCopy')
            ->with()
            ->willReturn([]);

        $out = $this->command->status('site.env');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals(['status' => 'current',], $out->getArrayCopy());
    }

    /**
     * Tests the upstream:updates:status command when the environment is outdated
     */
    public function testStatusOudated()
    {
        $this->commits->expects($this->once())
            ->method('getReadyToCopy')
            ->with()
            ->willReturn(['something',]);

        $out = $this->command->status('site.env');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals(['status' => 'outdated',], $out->getArrayCopy());
    }
}
