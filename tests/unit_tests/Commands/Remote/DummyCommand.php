<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remote;

use Pantheon\Terminus\Commands\Remote\SSHBaseCommand;

/**
 * Class DummyCommand
 * DummyCommand to exercise with SSHBaseCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class DummyCommand extends SSHBaseCommand
{
    protected $command = 'dummy';

    protected $unavailable_commands = [
        'avoided'        => 'alternative',
        'no-alternative' => '',
    ];

    protected $valid_frameworks = [
        'framework-a',
        'framework-b',
    ];

    public function dummyCommand($site_env_id, array $dummy_args)
    {
        $this->prepareEnvironment($site_env_id);

        return $this->executeCommand($dummy_args);
    }
}
