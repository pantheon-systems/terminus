<?php

namespace Pantheon\Terminus\UnitTests\Commands\Tag;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Tag\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Tag\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Tag
 */
class ListCommandTest extends TagCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the tag:list command when there are tags to display
     */
    public function testListTags()
    {
        $tags = ['tag1', 'tag2',];

        $this->tags->expects($this->once())
            ->method('ids')
            ->with()
            ->willReturn($tags);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->listTags($this->site->id, $this->organization->id);
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($out->getArrayCopy(), $tags);
    }

    /**
     * Tests the tag:list command when there are no tags to display
     */
    public function testListTagsWhenEmpty()
    {
        $tags = [];

        $this->tags->expects($this->once())
            ->method('ids')
            ->with()
            ->willReturn($tags);
        $this->expectGetNames();
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{org} does not have any tags for {site}.'),
                $this->equalTo(['site' => $this->site_name, 'org' => $this->org_name,])
            );

        $out = $this->command->listTags($this->site->id, $this->organization->id);
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($out->getArrayCopy(), $tags);
    }
}
