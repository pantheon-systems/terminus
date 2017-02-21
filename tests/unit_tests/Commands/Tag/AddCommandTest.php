<?php

namespace Pantheon\Terminus\UnitTests\Commands\Tag;

use Pantheon\Terminus\Commands\Tag\AddCommand;

/**
 * Class AddCommandTest
 * Testing class for Pantheon\Terminus\Commands\Tag\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Tag
 */
class AddCommandTest extends TagCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new AddCommand($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the tag:add command
     */
    public function testAdd()
    {
        $tag = 'tag';

        $this->tags->expects($this->once())
            ->method('create')
            ->with($this->equalTo($tag));
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->org_name);
        $this->expectGetNames();
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{org} has tagged {site} with {tag}.'),
                $this->equalTo(['site' => $this->site_name, 'org' => $this->org_name, 'tag' => $tag,])
            );

        $out = $this->command->add($this->site->id, $this->organization->id, $tag);
        $this->assertNull($out);
    }
}
