<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\LookupCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class LookupCommandTest
 * Testing class for Pantheon\Terminus\Commands\Domain\LookupCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class LookupCommandTest extends DomainTest
{
    /**
     * @var string
     */
    protected $site_name;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new LookupCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);

        $this->site->id = 'site_id';
        $this->site_name = 'site_name';

        $this->sites->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->site,]);

        $this->domains->expects($this->any())
            ->method('fetch')
            ->with()
            ->willReturn($this->domains);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('This operation may take a long time to run.')
            );
    }

    /**
     * Tests the domain:lookup command
     */
    public function testLookup()
    {
        $domain = 'some.domain';

        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($this->site_name);

        $this->domains->expects($this->any())
            ->method('has')
            ->with($this->equalTo($domain))
            ->willReturn(true);

        $out = $this->command->lookup($domain);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\PropertyList', $out);

        $array_out = $out->getArrayCopy();
        $this->assertEquals($array_out['site_id'], $this->site->id);
        $this->assertEquals($array_out['site_name'], $this->site_name);
        $this->assertEquals($array_out['env_id'], 'dev');
    }

    /**
     * Tests the domain:lookup command when the domain is not present
     */
    public function testLookupDNE()
    {
        $domain = 'some.domain';

        $this->domains->expects($this->any())
            ->method('has')
            ->with($this->equalTo($domain))
            ->willReturn(false);

        $this->setExpectedException(TerminusNotFoundException::class);

        $out = $this->command->lookup($domain);
        $this->assertNull($out);
    }
}
