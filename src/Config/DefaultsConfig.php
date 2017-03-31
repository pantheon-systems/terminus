<?php

namespace Pantheon\Terminus\Config;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class DefaultsConfig
 * @package Pantheon\Terminus\Config
 */
class DefaultsConfig extends TerminusConfig
{
    /**
     * DefaultsConfig constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->set('root', $this->getTerminusRoot());
        $this->set('php', $this->getPhpBinary());
        $this->set('php_version', PHP_VERSION);
        $this->set('php_ini', get_cfg_var('cfg_file_path'));
        $this->set('script', $this->getTerminusScript());
        $this->set('os_version', php_uname('v'));
        $this->set('user_home', $this->getHomeDir());
    }

    /**
     * Get the name of the source for this configuration object.
     *
     * @return string
     */
    public function getSourceName()
    {
        return 'Default';
    }

    /**
     * Returns location of PHP with which to run Terminus
     *
     * @return string
     */
    protected function getPhpBinary()
    {
        return defined('PHP_BINARY') ? PHP_BINARY : 'php';
    }

    /**
     * Finds and returns the root directory of Terminus
     *
     * @param string $current_dir Directory to start searching at
     * @return string
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getTerminusRoot($current_dir = null)
    {
        if (is_null($current_dir)) {
            $current_dir = dirname(__DIR__);
        }
        if (file_exists($current_dir . DIRECTORY_SEPARATOR . 'composer.json')) {
            return $current_dir;
        }
        $dir = explode(DIRECTORY_SEPARATOR, $current_dir);
        array_pop($dir);
        if (empty($dir)) {
            throw new TerminusException('Could not locate root to set TERMINUS_ROOT.');
        }
        $dir = implode(DIRECTORY_SEPARATOR, $dir);
        $root_dir = $this->getTerminusRoot($dir);
        return $root_dir;
    }

    /**
     * Finds and returns the name of the script running Terminus functions
     *
     * @return string
     */
    protected function getTerminusScript()
    {
        $debug           = debug_backtrace();
        $script_location = array_pop($debug);
        $script_name     = str_replace(
            $this->getTerminusRoot() . DIRECTORY_SEPARATOR,
            '',
            $script_location['file']
        );
        return $script_name;
    }

    /**
     * Returns the appropriate home directory.
     *
     * Adapted from Terminus Package Manager by Ed Reel
     * @author Ed Reel <@uberhacker>
     * @url    https://github.com/uberhacker/tpm
     *
     * @return string
     */
    protected function getHomeDir()
    {
        $home = getenv('HOME');
        if (!$home) {
            $system = '';
            if (getenv('MSYSTEM') !== null) {
                $system = strtoupper(substr(getenv('MSYSTEM'), 0, 4));
            }
            if ($system != 'MING') {
                $home = getenv('HOMEPATH');
            }
        }
        return $home;
    }
}
