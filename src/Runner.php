<?php

namespace Pantheon\Terminus;

use Consolidation\AnnotatedCommand\AnnotatedCommandFactory;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;

class Runner
{
    /**
     * @var Terminus
     */
    private $application;
    /**
     * @var string
     */
    private $commands_directory;
    /**
     * @var string
     */
    private $top_namespace = 'Pantheon\Terminus\Commands';

    /**
     * Runner constructor
     *
     * @param array $options Options to configure the runner
     * @param \Pantheon\Terminus\Terminus $application
     */
    public function __construct(array $options = [], Terminus $application = null)
    {
        $this->commands_directory = __DIR__ . '/Commands';
        $this->application = $application;
        $this->configureApplication(new Config($options));
    }

    /**
     * Runs the instantiated Terminus application
     */
    public function run()
    {
        $this->application->run();
    }

    /**
     * Adds command files to the application
     *
     * @param Config $config A Terminus configuration object
     * @return void
     */
    private function configureApplication(Config $config)
    {
        $command_factory = new AnnotatedCommandFactory();
        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php');
        $subdirectories = array_merge(
            $this->locateSubdirectories($this->commands_directory, $this->top_namespace),
            $this->locateSubdirectories($config->get('plugins_dir'))
        );
        foreach ($subdirectories as $subdirectory) {
            $command_files = $discovery->discover($subdirectory['path'], $subdirectory['namespace']);
            foreach ($command_files as $command_class) {
                $reflection_class = new \ReflectionClass($command_class);
                if (!$reflection_class->isAbstract()) {
                    $command_list = $command_factory->createCommandsFromClass(new $command_class($config));
                    foreach ($command_list as $command) {
                        $this->application->add($command);
                    }
                }
            }
        }
    }

    /**
     * Recursively discovers all directories underneath a given directory
     *
     * @param $directory Directory to start search from
     * @param $namespace Namespace for the given directory
     */
    private function locateSubdirectories($directory, $namespace = '\\')
    {
        $subdirectories = [['path' => $directory, 'namespace' => $namespace,]];
        $dirs = array_filter(glob("$directory/*"), 'is_dir');
        foreach ($dirs as $dir) {
            $next_namespace = $namespace.'\\'.str_replace("$directory/", '', $dir);
            $subdirectories = array_merge($subdirectories, $this->locateSubdirectories($dir, $next_namespace));
        }
        return $subdirectories;
    }
}
