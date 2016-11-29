<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Site\InfoCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new InfoCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises site:info
     */
    public function testSiteImportValidURL()
    {
        $this->site->expects($this->once())
            ->method('serialize')
            ->willReturn(['data' => 'array',]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->info('my-site');
        $this->assertInstanceOf(PropertyList::class, $out);
    }
}
