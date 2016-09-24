<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\DeployCommand;
use Terminus\Collections\Sites;

/**
 * Testing class for Pantheon\Terminus\Commands\Env\DeployCommand
 */
class DeployCommandTest extends EnvCommandTest
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new DeployCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }


    /**
     * Tests the env:deploy command success with all parameters.
     *
     * @return void
     */
    public function testDeploy()
    {
        $this->env->id = 'test';

        $this->env->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(true);

        $this->env->expects($this->once())
            ->method('deploy')
            ->willReturn($this->workflow)
            ->with([
                'updatedb' => 0,
                'clear_cache' => 0,
                'annotation' => 'Deploy from Terminus',
                'clone_database' => [
                    'from_environment' => 'live'
                ],
                'clone_files' => [
                    'from_environment' => 'live'
                ]
            ]);

        $this->workflow->expects($this->once())
            ->method('wait');

        // Run the deploy.
        $this->command->deploy('mysite.test', [
            'sync-content' => true,
            'note' => 'Deploy from Terminus',
            'cc' => false,
            'updatedb' => false,
        ]);
    }

    /**
     * Tests the env:deploy command where no code is deployable.
     *
     * @return void
     */
    public function testDeployNoCode()
    {
        $this->env->id = 'test';

        $this->env->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(false);

        $this->env->expects($this->never())
            ->method('deploy');

        $this->logger->expects($this->once())
            ->method('log');

        // Run the deploy.
        $this->command->deploy('mysite.test');
    }

    /**
     * Tests the env:deploy command to live.
     *
     * @return void
     */
    public function testDeployLive()
    {
        $this->env->id = 'live';

        $this->env->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(true);

        $this->env->expects($this->once())
            ->method('deploy')
            ->willReturn($this->workflow)
            ->with([
                'updatedb' => 1,
                'clear_cache' => 1,
                'annotation' => 'Deploy from Terminus',
            ]);

        $this->workflow->expects($this->once())
            ->method('wait');

        // Run the deploy.
        $this->command->deploy('mysite.live', [
            'sync-content' => true,
            'note' => 'Deploy from Terminus',
            'cc' => true,
            'updatedb' => true,
        ]);
    }
}
