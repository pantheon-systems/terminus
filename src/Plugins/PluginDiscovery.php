<?php

namespace Pantheon\Terminus\Plugins;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Pantheon\Terminus\Plugins\PluginInfo;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Class PluginDiscovery
 */
class PluginDiscovery implements ContainerAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    /**
     * List of all Terminus plugins that have been rolled into Terminus core.
     */
    const BLACKLIST = [
        'pantheon-systems/terminus-aliases-plugin'
    ];

    /**
     * @var string The path to the directory to search for plugins.
     */
    protected $directory_path;

    /**
     * PluginDiscovery constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->directory_path = $path;
    }

    /**
     * Autoload plugins.
     */
    public function autoloadPlugins() {
        // @todo Kevin Change to terminus-dependencies path when ready.
        $autoload_path = $this->directory_path . '/vendor/autoload.php';
        $local_machine = $this->getContainer()->get(LocalMachineHelper::class);
        if ($local_machine->getFilesystem()->exists($autoload_path)) {
            include $autoload_path;
        }
    }

    /**
     * Return a list of plugin
     *
     * @return PluginInfo[]
     */
    public function discover()
    {
        $out = [];
        $composer_lock = [];
        try {
            $local_machine = $this->getContainer()->get(LocalMachineHelper::class);
            if ($local_machine->getFilesystem()->exists($this->directory_path . '/composer.lock')) {
                $composer_lock = \json_decode(
                    file_get_contents($this->directory_path . '/composer.lock'),
                    true,
                    10,
                    JSON_THROW_ON_ERROR
                );
            }
            if (empty($composer_lock['packages'])) {
                return $out;
            }
        } catch (\Exception $e) {
            return $out;
            // Plugin directory probably didn't exist or wasn't writable. Do nothing.
        }

        foreach ($composer_lock['packages'] as $package) {
            try {
                if (empty($package['type']) || $package['type'] !== 'terminus-plugin') {
                    continue;
                }
                $plugin = $this->getContainer()->get(PluginInfo::class);
                $plugin->setInfoArray($package);
                if (!in_array($plugin->getName(), self::BLACKLIST)) {
                    $out[] = $plugin;
                }
            } catch (TerminusException $e) {
                $this->logger->warning(
                    'Plugin Discovery: Ignoring directory {dir} because: {msg}.',
                    ['dir' => $dir->getPathName(), 'msg' => $e->getMessage()]
                );
            }
        }
        return $out;
    }
}
