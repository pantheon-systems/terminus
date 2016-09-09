<?php

namespace Pantheon\Terminus;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Symfony\Component\Console\Application;
use Robo\Runner as RoboRunner;

class Runner
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var \Robo\Runner
     */
    private $runner;

    /**
     * Object constructor
     *
     * @param array $options Elements as follow:
     *        Application application An instance of a Symfony Console Application
     *        Config      config      A Terminus config instance
     *        Container   container   The Dependency Injection Container
     */
    public function __construct(array $options = ['application' => null, 'config' => null, 'container' => null])
    {
        $this->application = $options['application'];
        $this->config = $options['config'];

        $commands_directory = __DIR__ . '/Commands';
        $top_namespace = 'Pantheon\Terminus\Commands';
        $commands = $this->getCommands(['path' => $commands_directory, 'namespace' => $top_namespace,]);
        $this->runner = new RoboRunner($commands, null, $options['container']);
    }

    /**
     * Runs the instantiated Terminus application
     *
     * @param string[] $arguments Argv from the command line
     * @return integer The exiting status code of the application
     */
    public function run($input, $output)
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
