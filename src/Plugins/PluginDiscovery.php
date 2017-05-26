<?php

namespace Pantheon\Terminus\Plugins;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class PluginDiscovery
 */
class PluginDiscovery implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * Return a list of plugin
     *
     * @return PluginInfo[]
     */
    public function discover()
    {
        $out = [];
        try {
            $di = new \DirectoryIterator($this->directory_path);
        } catch (\Exception $e) {
            return $out;
            // Plugin directory probably didn't exist or wasn't writable. Do nothing.
        }

        foreach ($di as $dir) {
            if ($dir->isDir() && !$dir->isDot() && $dir->isReadable()) {
                try {
                    $plugin = new PluginInfo($dir->getPathname());
                    $out[] = $plugin;
                } catch (TerminusException $e) {
                    $this->logger->warning(
                        'Plugin Discovery: Ignoring directory {dir} because: {msg}.',
                        ['dir' => $dir->getPathName(), 'msg' => $e->getMessage()]
                    );
                }
            }
        }
        return $out;
    }
}
