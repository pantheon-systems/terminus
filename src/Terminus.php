<?php

namespace Pantheon\Terminus;

use Composer\Autoload\ClassLoader;
use Composer\Semver\Semver;
use League\Container\Container;
use Pantheon\Terminus\API\API;
use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Plugins\PluginAutoloadDependencies;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\ProgressBars\ProcessProgressBar;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Update\LatestRelease;
use Pantheon\Terminus\Update\UpdateChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Config\Config;
use Robo\Runner;
use SelfUpdate\SelfUpdateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VCR\VCR;

/**
 * Class Terminus
 * @package Pantheon\Terminus
 */
class Terminus extends API implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    private $commands = [];
    /**
     * @var Runner
     */
    private $runner;

    /**
     * Object constructor
     *
     * @param Config $config
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(Config $config, InputInterface $input = null, OutputInterface $output = null)
    {
        $application = new Application('Terminus', $config->get('version'));
        $application = self::addDefaultArgumentsAndOptions($application);
        parent::__construct($config, $input, $output, $application);

        $container = $this->getContainer();

        $this->setLogger($container->get('logger'));

        $this->setBuiltInCommandsAndHooks();
        $this->addPluginsCommandsAndHooks();

        if (\Phar::running(true)) {
            $cmd = new SelfUpdateCommand('Terminus', $config->get('version'), 'pantheon-systems/terminus');
            $application->add($cmd);
        }
        $this->runner = new RoboRunner();
        $this->runner->setContainer($container);

        date_default_timezone_set($config->get('time_zone'));
        setlocale(LC_MONETARY, $config->get('monetary_locale'));
    }

    /**
     * Runs the instantiated Terminus application
     *
     * @param InputInterface $input An input object to run the application with
     * @param OutputInterface $output An output object to run the application with
     * @return integer $status_code The exiting status code of the application
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig();
        if (!empty($cassette = $config->get('vcr_cassette')) && !empty($mode = $config->get('vcr_mode'))) {
            self::startVCR(array_merge(compact('cassette'), compact('mode')));
        }
        $status_code = $this->runner->run($input, $output, null, $this->commands);
        if (!empty($cassette) && !empty($mode)) {
            self::stopVCR();
        } elseif ($input->isInteractive()) {
            $this->runUpdateChecker();
        }
        return $status_code;
    }

    /**
     * {@inheritDoc}
     */
    protected static function configureContainer(Container $container, Config $config)
    {
        $container = parent::configureContainer($container, $config);

        // Progress Bars
        $container->add(ProcessProgressBar::class);
        $container->add(WorkflowProgressBar::class);

        // Plugin handlers
        $container->share('pluginAutoloadDependencies', PluginAutoloadDependencies::class)
            ->withArgument(__DIR__);
        $container->add(PluginDiscovery::class)
            ->withArgument($this->getConfig()->get('plugins_dir'));

        // Update checker
        $container->add(LatestRelease::class);
        $container->add(UpdateChecker::class);

        // Install our command cache into the command factory
        $commandCacheDir = $this->getConfig()->get('command_cache_dir');
        $commandCacheDataStore = new FileStore($commandCacheDir);

        $factory = $container->get('commandFactory');
        $factory->setIncludeAllPublicMethods(false);
        $factory->setDataStore($commandCacheDataStore);

        // Call our autoload loader at the beginning of any command dispatch.
        $pluginAutoloadDependencies = $container->get('pluginAutoloadDependencies');
        $factory->hookManager()->addInitializeHook($pluginAutoloadDependencies);

        return $container;
    }

    /**
     * Discovers command classes using CommandFileDiscovery
     */
    private function addPluginsCommandsAndHooks()
    {
        // Rudimentary plugin loading.
        $discovery = $this->getContainer()->get(PluginDiscovery::class);
        $plugins = $discovery->discover();
        $version = $this->config->get('version');
        $classLoader = new ClassLoader();
        $classLoader->register();
        foreach ($plugins as $plugin) {
            if (Semver::satisfies($version, $plugin->getCompatibleTerminusVersion())) {
                $plugin->autoloadPlugin($classLoader);
                $this->commands += $plugin->getCommandsAndHooks();
            } else {
                $this->logger->warning(
                    'Could not load plugin {plugin} because it is not compatible with this version of Terminus.',
                    ['plugin' => $plugin->getName(),]
                );
            }
        }
    }

    /**
     * Add the commands and hooks which are shipped with core Terminus
     */
    private function setBuiltInCommandsAndHooks()
    {
        // List of all hooks and commands. Update via 'composer update-class-lists'
        $this->commands = [
            'Consolidation\\Filter\\Hooks\\FilterHooks',
            'Pantheon\\Terminus\\Hooks\\Authorizer',
            'Pantheon\\Terminus\\Hooks\\RoleValidator',
            'Pantheon\\Terminus\\Hooks\\SiteEnvLookup',
            'Pantheon\\Terminus\\Commands\\AliasesCommand',
            'Pantheon\\Terminus\\Commands\\ArtCommand',
            'Pantheon\\Terminus\\Commands\\Auth\\LoginCommand',
            'Pantheon\\Terminus\\Commands\\Auth\\LogoutCommand',
            'Pantheon\\Terminus\\Commands\\Auth\\WhoamiCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\Automatic\\DisableCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\Automatic\\EnableCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\Automatic\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\BackupCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\CreateCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\GetCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\RestoreCommand',
            'Pantheon\\Terminus\\Commands\\Backup\\SingleBackupCommand',
            'Pantheon\\Terminus\\Commands\\Branch\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Connection\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Connection\\SetCommand',
            'Pantheon\\Terminus\\Commands\\Dashboard\\ViewCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\AddCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\DNSCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\LookupCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\Primary\\AddCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\Primary\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Domain\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Env\\ClearCacheCommand',
            'Pantheon\\Terminus\\Commands\\Env\\CloneContentCommand',
            'Pantheon\\Terminus\\Commands\\Env\\CodeLogCommand',
            'Pantheon\\Terminus\\Commands\\Env\\CommitCommand',
            'Pantheon\\Terminus\\Commands\\Env\\DeployCommand',
            'Pantheon\\Terminus\\Commands\\Env\\DiffStatCommand',
            'Pantheon\\Terminus\\Commands\\Env\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Env\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Env\\MetricsCommand',
            'Pantheon\\Terminus\\Commands\\Env\\ViewCommand',
            'Pantheon\\Terminus\\Commands\\Env\\WakeCommand',
            'Pantheon\\Terminus\\Commands\\Env\\WipeCommand',
            'Pantheon\\Terminus\\Commands\\HTTPS\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\HTTPS\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\HTTPS\\SetCommand',
            'Pantheon\\Terminus\\Commands\\Import\\CompleteCommand',
            'Pantheon\\Terminus\\Commands\\Import\\DatabaseCommand',
            'Pantheon\\Terminus\\Commands\\Import\\FilesCommand',
            'Pantheon\\Terminus\\Commands\\Import\\SiteCommand',
            'Pantheon\\Terminus\\Commands\\Lock\\DisableCommand',
            'Pantheon\\Terminus\\Commands\\Lock\\EnableCommand',
            'Pantheon\\Terminus\\Commands\\Lock\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\MachineToken\\DeleteAllCommand',
            'Pantheon\\Terminus\\Commands\\MachineToken\\DeleteCommand',
            'Pantheon\\Terminus\\Commands\\MachineToken\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Multidev\\CreateCommand',
            'Pantheon\\Terminus\\Commands\\Multidev\\DeleteCommand',
            'Pantheon\\Terminus\\Commands\\Multidev\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Multidev\\MergeFromDevCommand',
            'Pantheon\\Terminus\\Commands\\Multidev\\MergeToDevCommand',
            'Pantheon\\Terminus\\Commands\\NewRelic\\DisableCommand',
            'Pantheon\\Terminus\\Commands\\NewRelic\\EnableCommand',
            'Pantheon\\Terminus\\Commands\\NewRelic\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Org\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Org\\People\\AddCommand',
            'Pantheon\\Terminus\\Commands\\Org\\People\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Org\\People\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Org\\People\\RoleCommand',
            'Pantheon\\Terminus\\Commands\\Org\\Site\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Org\\Site\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Org\\Upstream\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Owner\\SetCommand',
            'Pantheon\\Terminus\\Commands\\PaymentMethod\\AddCommand',
            'Pantheon\\Terminus\\Commands\\PaymentMethod\\ListCommand',
            'Pantheon\\Terminus\\Commands\\PaymentMethod\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Plan\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Plan\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Plan\\SetCommand',
            'Pantheon\\Terminus\\Commands\\Redis\\DisableCommand',
            'Pantheon\\Terminus\\Commands\\Redis\\EnableCommand',
            'Pantheon\\Terminus\\Commands\\Remote\\DrushCommand',
            'Pantheon\\Terminus\\Commands\\Remote\\SSHBaseCommand',
            'Pantheon\\Terminus\\Commands\\Remote\\WPCommand',
            'Pantheon\\Terminus\\Commands\\SSHKey\\AddCommand',
            'Pantheon\\Terminus\\Commands\\SSHKey\\ListCommand',
            'Pantheon\\Terminus\\Commands\\SSHKey\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Self\\ClearCacheCommand',
            'Pantheon\\Terminus\\Commands\\Self\\ConfigDumpCommand',
            'Pantheon\\Terminus\\Commands\\Self\\ConsoleCommand',
            'Pantheon\\Terminus\\Commands\\Self\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\ServiceLevel\\SetCommand',
            'Pantheon\\Terminus\\Commands\\Site\\CreateCommand',
            'Pantheon\\Terminus\\Commands\\Site\\DeleteCommand',
            'Pantheon\\Terminus\\Commands\\Site\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Site\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Site\\LookupCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Org\\AddCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Org\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Org\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Site\\SiteCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Team\\AddCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Team\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Team\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Team\\RoleCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Upstream\\ClearCacheCommand',
            'Pantheon\\Terminus\\Commands\\Site\\Upstream\\SetCommand',
            'Pantheon\\Terminus\\Commands\\Solr\\DisableCommand',
            'Pantheon\\Terminus\\Commands\\Solr\\EnableCommand',
            'Pantheon\\Terminus\\Commands\\Tag\\AddCommand',
            'Pantheon\\Terminus\\Commands\\Tag\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Tag\\RemoveCommand',
            'Pantheon\\Terminus\\Commands\\Tag\\TagCommand',
            'Pantheon\\Terminus\\Commands\\TerminusCommand',
            'Pantheon\\Terminus\\Commands\\Upstream\\InfoCommand',
            'Pantheon\\Terminus\\Commands\\Upstream\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Upstream\\Updates\\ApplyCommand',
            'Pantheon\\Terminus\\Commands\\Upstream\\Updates\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Upstream\\Updates\\StatusCommand',
            'Pantheon\\Terminus\\Commands\\Upstream\\Updates\\UpdatesCommand',
            'Pantheon\\Terminus\\Commands\\Workflow\\Info\\InfoBaseCommand',
            'Pantheon\\Terminus\\Commands\\Workflow\\Info\\LogsCommand',
            'Pantheon\\Terminus\\Commands\\Workflow\\Info\\OperationsCommand',
            'Pantheon\\Terminus\\Commands\\Workflow\\Info\\StatusCommand',
            'Pantheon\\Terminus\\Commands\\Workflow\\ListCommand',
            'Pantheon\\Terminus\\Commands\\Workflow\\WatchCommand',
        ];
    }

    /**
     * Runs the UpdateChecker to check for new Terminus versions
     */
    private function runUpdateChecker()
    {
        $file_store = new FileStore($this->getConfig()->get('cache_dir'));
        $this->runner->getContainer()->get(UpdateChecker::class, [$file_store,])->run();
    }

    /**
     * Add any global arguments or options that apply to all commands.
     *
     * @param Application $app
     * @return Application
     */
    private static function addDefaultArgumentsAndOptions(Application $app)
    {
        $app->getDefinition()->addOption(
            new InputOption('--yes', '-y', InputOption::VALUE_NONE, 'Answer all confirmations with "yes"')
        );
        return $app;
    }

    /**
     * Starts and configures PHP-VCR
     *
     * @param string[] $options Elements as follow:
     *        string cassette The name of the fixture in tests/fixtures to record or run in this feature test run
     *        string mode     Mode in which to run PHP-VCR (options are none, once, strict, and new_episodes)
     */
    private static function startVCR(array $options = ['cassette' => 'tmp', 'mode' => 'none',])
    {
        VCR::configure()->enableRequestMatchers(['method', 'url', 'body',]);
        VCR::configure()->setMode($options['mode']);
        VCR::turnOn();
        VCR::insertCassette($options['cassette']);
    }

    /**
     * Stops PHP-VCR's recording and playback
     */
    private static function stopVCR()
    {
        VCR::eject();
        VCR::turnOff();
    }
}
