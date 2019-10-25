<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain\Primary;

use Pantheon\Terminus\Commands\Domain\Primary\RemoveCommand;

class RemoveCommandTest extends PrimaryDomainCommandsTestBase
{
    protected function getSystemUnderTest()
    {
        return new RemoveCommand();
    }

    public function testRemove()
    {
        $site_name = 'site_name';
        $domain = null;
        $this->prepareTestSetReset(
            $domain,
            'Primary domain has been removed from {site}.{env}',
            ['site' => $this->site->get('name'), 'env' => $this->environment->id]
        );

        $out = $this->command->remove("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }
}
