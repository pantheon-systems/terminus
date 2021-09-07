<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

class MultiDevTest extends TestCase
{
    use TerminusTestTrait;
    use SiteBaseSetupTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Multidev\CreateCommand
     * @covers \Pantheon\Terminus\Commands\Multidev\ListCommand
     * @covers \Pantheon\Terminus\Commands\Multidev\DeleteCommand
     *
     * @group multidev
     * @group long
     */
    public function testMultidevCreateListDeleteCommands()
    {
        $sitename = $this->getSiteName();
        $envname = substr(uniqid('md-'), 0, 11);
        $this->terminus(
            vsprintf(
                "multidev:create %s.dev %s",
                [$sitename, $envname]
            ),
            null
        );
        sleep(10);
        $list = $this->terminusJsonResponse(
            vsprintf(
                "multidev:list %s",
                [$sitename]
            ),
            null
        );
        $envInfo = null;
        foreach ($list as $environment) {
            if ($environment['id'] == $envname) {
                $envInfo = $environment;
            }
        }
        $this->assertNotNull($envInfo, "newly-created environment should be in the environment list");
        $this->terminus(
            vsprintf(
                "multidev:delete %s.%s --delete-branch --yes",
                [$sitename, $envname]
            ),
            null
        );
    }
}
