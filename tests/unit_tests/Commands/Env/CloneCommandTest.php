<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

/**
 * Class CloneCommandTesto
 * Testing class for Pantheon\Terminus\Commands\Env\CloneCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class CloneCommandTest extends EnvCommandTest
{
    protected $command;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new MockCloneCommand();
    }

    /**
     * @test
     */
    public function startProgressBarReturnsProgressBar()
    {
        $this->assertInstanceOf(
            '\Symfony\Component\Console\Helper\ProgressBar',
            $this->protectedMethodCall($this->command, 'startProgressBar', ['database'])
        );
    }

    /**
     * @test
     */
    public function filterOperationsRemovesOperationsPerFlags()
    {
        $this->assertArrayNotHasKey(
            'cloneFiles',
            $this->protectedMethodCall(
                $this->command,
                'filterOperations',
                [['db-only' => true, 'files-only' => false]]
            )
        );

        $this->assertArrayNotHasKey(
            'cloneDatabase',
            $this->protectedMethodCall(
                $this->command,
                'filterOperations',
                [['files-only' => true, 'db-only' => false]]
            )
        );
    }
}
