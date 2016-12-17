<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Site\LookupCommand;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class LookupCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\LookupCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class LookupCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new LookupCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises site:lookup where the result is that the site exists and you have access to it
     */
    public function testSiteLookupExists()
    {
        $site_name = 'my-site';

        $this->site->expects($this->once())
            ->method('serialize')
            ->willReturn(['name' => $site_name, 'id' => 'site_id']);

        $out = $this->command->lookup($site_name);
        $this->assertInstanceOf(PropertyList::class, $out);

        $this->assertEquals(['name' => $site_name, 'id' => 'site_id'], $out->getArrayCopy());
    }

    /**
     * Exercises site:lookup where the result is that the site does not exist
     *
     * @expectedException \Exception
     * @expectedExceptionMessage A site named my-site was not found.
     */
    public function testSiteLooupDoesNotExist()
    {
        $site_name = 'my-site';

        $this->sites->method('get')
            ->with($this->equalTo($site_name))
            ->will($this->throwException(new \Exception("A site named $site_name was not found.")));

        $out = $this->command->lookup($site_name);
        $this->assertInstanceOf(PropertyList::class, $out);
    }
}
