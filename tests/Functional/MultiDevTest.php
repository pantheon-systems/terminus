<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

class MultiDevTest extends TestCase
{
    use TerminusTestTrait;
    use SiteBaseSetupTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers Pantheon\Terminus\Commands\Multidev\CreateCommand
     * @covers Pantheon\Terminus\Commands\Multidev\ListCommand
     * @covers Pantheon\Terminus\Commands\Multidev\DeleteCommand
     * @group multidev
     * @group long
     */
    public function testMultidevCreateCommand()
    {
        $sitename = getenv('TERMINUS_SITE');
        $envname = uniqid('multidev-test-');
        $this->terminus(
            vprintf(
                "multidev:create %s.%s -no-interactions",
                [$sitename, $envname]
            ),
            null
        );
        sleep(10);
        $list = $this->terminusJsonResponse(
            vprintf(
                "multidev:list %s",
                [$sitename]
            )
        );
        $envInfo = null;
        foreach ($list as $environment) {
            if ($environment['id'] == $envname) {
                $envInfo = $environment;
            }
        }
        $this->assertNotNull($envInfo, "newly-created environment should be in the environment list");
        $this->terminus(
            vprintf(
                "multisite:delete %s.%s",
                [$sitename, $envname]
            ),
            null
        );
    }
}
