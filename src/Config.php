<?php

namespace Pantheon\Terminus;

use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;
use Terminus\Exceptions\TerminusException;

class Config extends \Robo\Config
{
    /**
     * @var boolean
     */
    private $configured = false;

    /**
     * @var string
     */
    private $config_path = '/config/constants.yml';
    /**
     * @var string
     */
    private $constant_prefix = 'TERMINUS_';

    /**
     * Constructor for Config
     */
    public function __construct()
    {
    }

    /**
     * @inheritdoc
     */
    public function getGlobalOptionDefaultValues()
    {
        // We deliberately do not want to return any of the values from
        // the parent function here.  Any global CLI options that Terminus
        // adds should be defined here in an associative array that
        // returns the key => default value of the global options.
        return [];
    }

    /**
     * Ensures a directory exists
     *
     * @param string $name  The name of the config var
     * @param string $value The value of the named config var
     * @return boolean|null
     */
    public function ensureDirExists($name, $value)
    {
        if ((strpos($name, 'TERMINUS_') !== false) && (strpos($name, '_DIR') !== false) && ($value != '~')) {
            try {
                $dir_exists = (is_dir($value) || (!file_exists($value) && @mkdir($value, 0777, true)));
            } catch (\Exception $e) {
                return false;
            }
            return $dir_exists;
        }
        return null;
    }

    /**
     * Ensures that directory paths work in any system
     *
     * @param string $path A path to set the directory separators for
     * @return string
     */
    public function fixDirectorySeparators($path)
    {
        return str_replace(['/', '\\',], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @inheritdoc
     * @throws TerminusException
     */
    public function get($key, $defaultOverride = null)
    {
        if (!$this->configured) {
            $this->configure();
            $this->configured = true;
        }
        if (!isset($this->config[$key])) {
            throw new TerminusException('No configuration setting for {key} found.', compact('key'));
        }

        return parent::get($key);
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
    public function getHomeDir()
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

    /**
     * Sets constants necessary for the proper functioning of Terminus
     *
     * @return void
     */
    private function configure()
    {
        $this->importEnvironmentVariables();
        $this->config['root'] = $this->getTerminusRoot();
        $this->config['php'] = $this->getPhpBinary();
        $this->config['php_version'] = PHP_VERSION;
        $this->config['php_ini'] = get_cfg_var('cfg_file_path');
        $this->config['script'] = $this->getTerminusScript();
        $this->config['os_version'] = php_uname('v');

        $file_config = Yaml::parse(
            file_get_contents($this->config['root'] . $this->config_path)
        );
        foreach ($file_config as $name => $setting) {
            $key = $this->getKeyFromConstant($name);
            if (isset($this->config[$key])) {
                continue;
            } elseif (defined($name)) {
                $setting = constant($name);
            } elseif (isset($_SERVER[$name]) && ($_SERVER[$name] != '')) {
                $setting = $_SERVER[$name];
            } elseif (getenv($name)) {
                $setting = getenv($name);
            }
            $value = $this->replacePlaceholders($setting);
            $this->ensureDirExists($name, $value);
            $this->config[$key] = $value;
        }
        // TODO: revisit this: it seems that Terminus configuration
        // should not override the timezone set in php.ini.
        // date_default_timezone_set($this->get('time_zone'));
    }

    /**
     * Returns location of PHP with which to run Terminus
     *
     * @return string
     */
    private function getPhpBinary()
    {
        if (isset($this->config['php'])) {
            return $this->config['php'];
        }
        if (getenv('TERMINUS_PHP')) {
            $php_bin = getenv('TERMINUS_PHP');
        } elseif (defined('PHP_BINARY')) {
            $php_bin = PHP_BINARY;
        } else {
            $php_bin = 'php';
        }
        return $php_bin;
    }

    /**
     * Finds and returns the root directory of Terminus
     *
     * @param string $current_dir Directory to start searching at
     * @return string
     */
    private function getTerminusRoot($current_dir = null)
    {
        if (isset($this->config['root'])) {
            return $this->config['root'];
        }
        if (defined('TERMINUS_ROOT')) {
            return TERMINUS_ROOT;
        }

        if (is_null($current_dir)) {
            $current_dir = dirname(__DIR__);
        }
        if (file_exists("$current_dir/composer.json")) {
            return $current_dir;
        }
        $dir = explode('/', $current_dir);
        array_pop($dir);
        if (empty($dir)) {
            throw new TerminusError('Could not locate root to set TERMINUS_ROOT.');
        }
        $dir = implode('/', $dir);
        $root_dir = $this->getTerminusRoot($dir);
        return $root_dir;
    }

    /**
     * Finds and returns the name of the script running Terminus functions
     *
     * @return string
     */
    private function getTerminusScript()
    {
        if (isset($this->config['script'])) {
            return $this->config['script'];
        }
        if (defined('TERMINUS_SCRIPT')) {
            return TERMINUS_SCRIPT;
        }

        $debug           = debug_backtrace();
        $script_location = array_pop($debug);
        $script_name     = str_replace(
            $this->config['root'] . '/',
            '',
            $script_location['file']
        );
        return $script_name;
    }

    /**
     * Imports environment variables
     *
     * @return void
     */
    private function importEnvironmentVariables()
    {
        //Load environment variables from __DIR__/.env
        if (file_exists(getcwd() . '/.env')) {
            $env = new Dotenv(getcwd());
            $env->load();
        }
    }

    /**
     * Exchanges values in [[ ]] in the given string with constants
     *
     * @param string $string The string to perform replacements on
     * @return string $string The modified string
     */
    private function replacePlaceholders($string)
    {
        $regex = '~\[\[(.*?)\]\]~';
        preg_match_all($regex, $string, $matches);
        if (!empty($matches)) {
            foreach ($matches[1] as $id => $value) {
                $replacement_key = $this->getKeyFromConstant(trim($value));
                if (isset($this->config[$replacement_key])) {
                    $replacement = $this->config[$replacement_key];
                    $string = str_replace($matches[0][$id], $replacement, $string);
                }
            }
        }
        $fixed_string = $this->fixDirectorySeparators(
            str_replace('~', $this->getHomeDir(), $string)
        );
        return $fixed_string;
    }

    /**
     * Reflects a key name given a Terminus constant name
     *
     * @param string $constant_name The name of a constant to get a key for
     * @return string
     */
    private function getKeyFromConstant($constant_name)
    {
        $key = strtolower(str_replace($this->constant_prefix, '', $constant_name));
        return $key;
    }
}
