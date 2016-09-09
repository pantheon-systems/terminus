<?php

namespace Pantheon\Terminus;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use League\Container\Container;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Runner
{
    /**
     * @var \Robo\Runner
     */
    private $runner;

    /**
     * Object constructor
     *
     * @param Container $container Container The dependency injection container
     */
    public function __construct(Container $container = null)
    {
        $commands_directory = __DIR__ . '/Commands';
        $top_namespace = 'Pantheon\Terminus\Commands';
        $commands = $this->getCommands(['path' => $commands_directory, 'namespace' => $top_namespace,]);
        $this->runner = new RoboRunner($commands, null, $container);
    }

    /**
     * Runs the instantiated Terminus application
     *
     * @param InputInterface  $input  An input object to run the application with
     * @param OutputInterface $output An output object to run the application with
     * @return integer $status_code The exiting status code of the application
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->runner->init($input, $output);
        $status_code = $this->runner->run($input, $output);
        return $status_code;
    }

    /**
     * Discovers command classes using CommandFileDiscovery
     *
     * @param string[] $options Elements as follow
     *        string path      The full path to the directory to search for commands
     *        string namespace The full namespace associated with given the command directory
     * @return TerminusCommand[] An array of TerminusCommand instances
     */
    private function getCommands(array $options = ['path' => null, 'namespace' => null,])
    {
        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php')->setSearchLocations([]);
        return $discovery->discover($options['path'], $options['namespace']);
    }
}
