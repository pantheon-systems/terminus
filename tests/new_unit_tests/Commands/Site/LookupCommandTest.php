<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\Site\LookupCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Test suite class for Pantheon\Terminus\Commands\Site\LookupCommand
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

        $this->sites->method('findUuidByName')
            ->with($this->equalTo($site_name))
            ->willReturn(['name' => $site_name, 'id' => 'site_id',]);

        $out = $this->command->lookup($site_name);
        $this->assertInstanceOf(AssociativeList::class, $out);
    }

    /**
     * Exercises site:lookup where the result is that the site exists but you do not have access to it
     *
     * @expectedException \Exception
     * @expectedExceptionMessage You are not authorized for this site.
     */
    public function testSiteLookupExistsButNotAuthorized()
    {
        $site_name = 'my-site';

        $this->sites->method('findUuidByName')
            ->with($this->equalTo($site_name))
            ->will($this->throwException(new \Exception('You are not authorized for this site.')));

        $out = $this->command->lookup($site_name);
        $this->assertInstanceOf(AssociativeList::class, $out);
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

        $this->sites->method('findUuidByName')
            ->with($this->equalTo($site_name))
            ->will($this->throwException(new \Exception("A site named $site_name was not found.")));

        $out = $this->command->lookup($site_name);
        $this->assertInstanceOf(AssociativeList::class, $out);
    }
}
