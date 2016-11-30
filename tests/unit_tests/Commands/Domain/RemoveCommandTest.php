<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\RemoveCommand;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Domain\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class RemoveCommandTest extends DomainTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the domain:remove command
     */
    public function testRemove()
    {
        $site_name = 'site_name';
        $domain = 'some.domain';
        $this->environment->id = 'env_id';

        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);

        $this->domains->expects($this->once())
            ->method('get')
            ->with($this->equalTo($domain))
            ->willReturn($this->domain);
        $this->domain->expects($this->once())
            ->method('delete')
            ->with();
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Removed {domain} from {site}.{env}'),
                $this->equalTo(['domain' => $domain, 'site' => $site_name, 'env' => $this->environment->id,])
            );

        $out = $this->command->remove("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }
}
