<?php

namespace Pantheon\Terminus;

use Composer\Semver\Semver;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as HttpRequest;
use League\Container\Argument\RawArgument;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Collections\Bindings;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\Loadbalancers;
use Pantheon\Terminus\Collections\PaymentMethods;
use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Models\Backup;
use Pantheon\Terminus\Models\Binding;
use Pantheon\Terminus\Models\Branch;
use Pantheon\Terminus\Models\Commit;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Domain;
use Pantheon\Terminus\Models\PaymentMethod;
use Pantheon\Terminus\Models\Loadbalancer;
use Pantheon\Terminus\Models\Lock;
use Pantheon\Terminus\Models\MachineToken;
use Pantheon\Terminus\Models\NewRelic;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\OrganizationUserMembership;
use Pantheon\Terminus\Models\Redis;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\Solr;
use Pantheon\Terminus\Models\SSHKey;
use Pantheon\Terminus\Models\Tag;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\UpstreamStatus;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Models\UserSiteMembership;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Models\WorkflowOperation;
use Pantheon\Terminus\Plugins\PluginAutoload;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Plugins\PluginInfo;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Update\LatestRelease;
use Pantheon\Terminus\Update\UpdateChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Config;
use Robo\Contract\ConfigAwareInterface;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VCR\VCR;

/**
 * Class Terminus
 * @package Pantheon\Terminus
 */
class Terminus implements ConfigAwareInterface, ContainerAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    /**
     * @var \Robo\Runner
     */
    private $runner;
    /**
     * @var string[]
     */
    private $commands = [];

    /**
     * Object constructor
     *
     * @param \Robo\Config $config
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(Config $config, InputInterface $input = null, OutputInterface $output = null)
    {
        $this->setConfig($config);

        $application = new Application('Terminus', $config->get('version'));
        $container = Robo::createDefaultContainer($input, $output, $application, $config);
        $this->setContainer($container);

        $this->addDefaultArgumentsAndOptions($application);

        $this->configureContainer();

        $this->addBuiltInCommandsAndHooks();
        $this->addPluginsCommandsAndHooks();

        $this->runner = new RoboRunner();
        $this->runner->setContainer($container);

        $this->setLogger($container->get('logger'));

        date_default_timezone_set($config->get('time_zone'));
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
        $config = $this->getConfig();
        if (!empty($cassette = $config->get('vcr_cassette')) && !empty($mode = $config->get('vcr_mode'))) {
            $this->startVCR(array_merge(compact('cassette'), compact('mode')));
        }
        $status_code = $this->runner->run($input, $output, null, $this->commands);
        if (!empty($cassette) && !empty($mode)) {
            $this->stopVCR();
        } else {
            $this->runUpdateChecker();
        }
        return $status_code;
    }

    /**
     * Add the commands and hooks which are shipped with core Terminus
     */
    private function addBuiltInCommandsAndHooks()
    {
        $commands = $this->getCommands([
            'path' => __DIR__ . '/Commands',
            'namespace' => 'Pantheon\Terminus\Commands',
        ]);
        $hooks = [
            'Pantheon\Terminus\Hooks\Authorizer',
        ];
        $this->commands = array_merge($commands, $hooks);
    }

    /**
     * Add any global arguments or options that apply to all commands.
     *
     * @param \Symfony\Component\Console\Application $app
     */
    private function addDefaultArgumentsAndOptions(Application $app)
    {
        $app->getDefinition()->addOption(new InputOption('--yes', '-y', InputOption::VALUE_NONE, 'Answer all confirmations with "yes"'));
    }

    /**
     * Discovers command classes using CommandFileDiscovery
     */
    private function addPluginsCommandsAndHooks()
    {
        // Rudimentary plugin loading.
        $discovery = $this->getContainer()->get(PluginDiscovery::class, [$this->getConfig()->get('plugins_dir')]);
        $plugins = $discovery->discover();
        $version = $this->config->get('version');
        foreach ($plugins as $plugin) {
            if (Semver::satisfies($version, $plugin->getCompatibleTerminusVersion())) {
                $this->commands += $plugin->getCommandsAndHooks();
            } else {
                $this->logger->warning(
                    "Could not load plugin {plugin} because it is not compatible with this version of Terminus.",
                    ['plugin' => $plugin->getName()]
                );
            }
        }
    }

    /**
     * Register the necessary classes for Terminus
     */
    private function configureContainer()
    {
        $container = $this->getContainer();

        // Add the services
        // Request
        $container->add(Client::class);
        $container->add(HttpRequest::class);
        $container->share('request', Request::class);
        $container->inflector(RequestAwareInterface::class)
            ->invokeMethod('setRequest', ['request']);

        // Session
        $session_store = new FileStore($this->getConfig()->get('cache_dir'));
        $session = new Session($session_store);
        $container->share('session', $session);
        $container->inflector(SessionAwareInterface::class)
            ->invokeMethod('setSession', ['session']);

        // Saved tokens
        $token_store = new FileStore($this->getConfig()->get('tokens_dir'));
        $container->inflector(SavedTokens::class)
            ->invokeMethod('setDataStore', [$token_store]);

        // Add the models and collections
        $container->add(User::class);
        $container->add(SavedTokens::class);
        $container->add(SavedToken::class);
        $container->add(PaymentMethods::class);
        $container->add(PaymentMethod::class);
        $container->add(SSHKeys::class);
        $container->add(SSHKey::class);
        $container->add(Workflows::class);
        $container->add(Workflow::class);
        $container->add(WorkflowOperation::class);
        $container->add(Loadbalancers::class);
        $container->add(MachineTokens::class);
        $container->add(MachineToken::class);
        $container->add(Upstream::class);
        $container->add(Upstreams::class);
        $container->add(UpstreamStatus::class);
        $container->add(UserSiteMemberships::class);
        $container->add(UserSiteMembership::class);
        $container->add(UserOrganizationMemberships::class);
        $container->add(UserOrganizationMembership::class);
        $container->add(OrganizationSiteMemberships::class);
        $container->add(OrganizationSiteMembership::class);
        $container->add(OrganizationUserMemberships::class);
        $container->add(OrganizationUserMembership::class);
        $container->add(Organization::class);
        $container->add(Branches::class);
        $container->add(Branch::class);
        $container->add(SiteUserMemberships::class);
        $container->add(SiteUserMembership::class);
        $container->add(SiteOrganizationMemberships::class);
        $container->add(SiteOrganizationMembership::class);
        $container->add(Site::class);
        $container->add(Redis::class);
        $container->add(Solr::class);
        $container->add(Environments::class);
        $container->add(Environment::class);
        $container->add(Backups::class);
        $container->add(Backup::class);
        $container->add(Loadbalancer::class);
        $container->add(Lock::class);
        $container->add(Bindings::class);
        $container->add(Binding::class);
        $container->add(Domains::class);
        $container->add(Domain::class);
        $container->add(Commits::class);
        $container->add(Commit::class);
        $container->add(NewRelic::class);
        $container->add(Tags::class);
        $container->add(Tag::class);

        // Helpers
        $container->add(LocalMachineHelper::class);

        // Plugin handlers
        $container->share('pluginAutoload', PluginAutoload::class);
        $container->add(PluginDiscovery::class);
        $container->add(PluginInfo::class);

        // Update checker
        $container->add(LatestRelease::class);
        $container->add(UpdateChecker::class);

        $container->share('sites', Sites::class);
        $container->inflector(SiteAwareInterface::class)
            ->invokeMethod('setSites', ['sites']);

        // Install our command cache into the command factory
        $commandCacheDir = $this->getConfig()->get('command_cache_dir');
        $commandCacheDataStore = new FileStore($commandCacheDir);

        $factory = $container->get('commandFactory');
        $factory->setIncludeAllPublicMethods(false);
        $factory->setDataStore($commandCacheDataStore);

        // Call our autoload loader at the beginning of any command dispatch.
        $pluginAutoload = $container->get('pluginAutoload');
        $factory->hookManager()->addInitializeHook($pluginAutoload);
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

    /**
     * Runs the UpdateChecker to check for new Terminus versions
     */
    private function runUpdateChecker()
    {
        $file_store = new FileStore($this->getConfig()->get('cache_dir'));
        $this->runner->getContainer()->get(UpdateChecker::class, [$file_store,])->run();
    }

    /**
     * Starts and configures PHP-VCR
     *
     * @param string[] $options Elements as follow:
     *        string cassette The name of the fixture in tests/fixtures to record or run in this feature test run
     *        string mode     Mode in which to run PHP-VCR (options are none, once, strict, and new_episodes)
     * @return void
     */
    private function startVCR(array $options = ['cassette' => 'tmp', 'mode' => 'none',])
    {
        VCR::configure()->enableRequestMatchers(['method', 'url', 'body',]);
        VCR::configure()->setMode($options['mode']);
        VCR::turnOn();
        VCR::insertCassette($options['cassette']);
    }

    /**
     * Stops PHP-VCR's recording and playback
     *
     * @return void
     */
    private function stopVCR()
    {
        VCR::eject();
        VCR::turnOff();
    }
}
