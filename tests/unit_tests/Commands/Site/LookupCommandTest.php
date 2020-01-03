<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Site\LookupCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
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
    protected function setUp(): void
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
}
