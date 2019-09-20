<?php


namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\Primary\SetCommand;
use Pantheon\Terminus\UnitTests\Commands\Domain\Primary\PrimaryDomainCommandsTestBase;

/**
 * Class SetCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Domain\Primary\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain\Primary
 */
class SetCommandTest extends PrimaryDomainCommandsTestBase
{
    protected function getSystemUnderTest()
    {
        return new SetCommand();
    }

    public function testSet()
    {
        $site_name = 'site_name';
        $domain = 'some.domain';
        $this->prepareTestSetReset(
            $domain,
            'Set {domain} as primary for {site}.{env}',
            ['domain' => $domain, 'site' => $this->site->get('name'), 'env' => $this->environment->id]
        );

        $out = $this->command->set("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }
}
