<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\CommitCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Env\CommitCommand
 */
class CommitCommandTest extends EnvCommandTest
{
    /**
     * Sets up the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CommitCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->environment->id = 'dev';
    }

    /**
     * Tests the env:commit command success with all parameters.
     *
     * @return void
     */
    public function testCommit()
    {
        $this->environment->expects($this->once())
            ->method('diffstat')
            ->willReturn(['a', 'b']);

        $this->environment->expects($this->once())
            ->method('commitChanges')
            ->willReturn($this->workflow)
            ->with('Custom message.');

        $this->workflow->expects($this->once())
            ->method('wait');

        $this->command->commit('mysite.dev', ['message' => 'Custom message.']);
    }
}
