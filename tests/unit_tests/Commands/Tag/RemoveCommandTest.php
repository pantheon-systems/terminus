<?php

namespace Pantheon\Terminus\UnitTests\Commands\Tag;

use Pantheon\Terminus\Commands\Tag\RemoveCommand;
use Pantheon\Terminus\Models\Tag;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Tag\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Tag
 */
class RemoveCommandTest extends TagCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the tag:remove command
     */
    public function testRemove()
    {
        $tag_string = 'tag';

        $tag = $this->getMockBuilder(Tag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tags->expects($this->once())
            ->method('get')
            ->with($this->equalTo($tag_string))
            ->willReturn($tag);
        $tag->expects($this->once())
            ->method('delete')
            ->with();
        $this->expectGetNames();
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{org} has removed the {tag} tag from {site}.'),
                $this->equalTo(['site' => $this->site_name, 'org' => $this->org_name, 'tag' => $tag_string,])
            );

        $out = $this->command->remove($this->site->id, $this->organization->id, $tag_string);
        $this->assertNull($out);
    }
}
