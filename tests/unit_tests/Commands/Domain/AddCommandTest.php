<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\AddCommand;

/**
 * Class AddCommandTest
 * Testing class for Pantheon\Terminus\Commands\Domain\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class AddCommandTest extends DomainTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new AddCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the domain:add command
     */
    public function testAdd()
    {
        $site_name = 'site_name';
        $domain = 'some.domain';
        $this->environment->id = 'env_id';

        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);

        $this->domains->expects($this->once())
            ->method('create')
            ->with($this->equalTo($domain));
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Added {domain} to {site}.{env}'),
                $this->equalTo(['domain' => $domain, 'site' => $site_name, 'env' => $this->environment->id,])
            );

        $out = $this->command->add("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }
}
