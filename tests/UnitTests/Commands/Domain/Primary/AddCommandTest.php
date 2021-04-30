<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain\Primary;

use Pantheon\Terminus\Commands\Domain\Primary\AddCommand;

/**
 * Class AddCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Domain\Primary\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain\Primary
 */
class AddCommandTest extends PrimaryDomainCommandsTestBase
{
    protected function getSystemUnderTest()
    {
        return new AddCommand();
    }

    public function testAdd()
    {
        $site_name = 'site_name';
        $domain = 'some.domain';
        $this->prepareTestSetReset(
            $domain,
            'Set {domain} as primary for {site}.{env}',
            ['domain' => $domain, 'site' => $this->site->get('name'), 'env' => $this->environment->id]
        );

        $out = $this->command->add("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }

    public function testFilterPlatformDomains()
    {
        $sys_under_test = new AddCommand();
        $this->assertEquals(
            array_values([
                'x',
                'something.com',
                'averylong.domain.ofsomeosrt.tld',
            ]),
            array_values($sys_under_test->filterPlatformDomains(
                [
                    'x',
                    'something.com',
                    'dev-mikes-testsite.pantheonsite.io',
                    'averylong.domain.ofsomeosrt.tld',
                ]
            ))
        );
    }
}
