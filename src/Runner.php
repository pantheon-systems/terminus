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
     */
    public function __construct(array $options = ['application' => null, 'config' => null,])
    {
        $this->application = $options['application'];
        $this->config = $options['config'];

        $commands_directory = __DIR__ . '/Commands';
        $top_namespace = 'Pantheon\Terminus\Commands';
        $commands = $this->getCommands(['path' => $commands_directory, 'namespace' => $top_namespace,]);
        $this->runner = new RoboRunner($commands);
    }

    /**
     * Runs the instantiated Terminus application
     *
     * @param string[] $arguments Argv from the command line
     * @return integer The exiting status code of the application
     */
    public function run(array $arguments = [])
    {
        $status_code = $this->runner->execute($arguments, null, 'Terminus', $this->config->get('version'));
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
        $discovery->setSearchPattern('*Command.php')->setSearchLocations(['.']);
        $commands = $this->fixCommands($discovery->discover($options['path'], $options['namespace']));
        return $commands;
    }

    /**
     * Removes extraneous /. and \. from the file and class nammes
     *
     * @param string[] $command_info A hash of command file names and paths
     * @return TerminusCommand[] A fixed and pruned hash of file names and paths
     */
    private function fixCommands($command_info)
    {
        $commands = array_filter(
            array_combine(
                array_map(
                    function ($file_name) {
                        return str_replace('/./', '/', $file_name);
                    },
                    array_keys($command_info)
                ),
                array_map(
                    function ($class_name) {
                        return str_replace('\\.\\', '\\', $class_name);
                    },
                    $command_info
                )
            ),
            function ($class_name) {
                $reflection_class = new \ReflectionClass($class_name);
                return !$reflection_class->isAbstract();
            }
        );
        return $commands;
    }
}
