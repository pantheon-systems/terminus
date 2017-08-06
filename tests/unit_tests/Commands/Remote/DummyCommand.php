<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remote;

use Pantheon\Terminus\Commands\Remote\SSHBaseCommand;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class DummyCommand
 * DummyCommand to exercise with SSHBaseCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class DummyCommand extends SSHBaseCommand
{
    /**
     * @var string
     */
    protected $command = 'dummy';

    public function dummyCommand($site_env_id, array $dummy_args)
    {
        $this->prepareEnvironment($site_env_id);
        return $this->executeCommand($dummy_args);
    }

    /**
     * @param InputInterface $input
     * @return bool|null
     */
    public function useUseTty($input)
    {
        return $this->useTty($input);
    }
}
