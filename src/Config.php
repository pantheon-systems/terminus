<?php

namespace Pantheon\Terminus;

use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class Config
{
    /**
     * @var string[]
     */
    private static $config = [];
    /**
     * @var string
     */
    private static $config_path = '/config/constants.yml';
    /**
     * @var string
     */
    private static $constant_prefix = 'TERMINUS_';

    /**
     * Constructor for Config
     *
     * @param array $options Options with which to configure this object
     */
    public function __construct(array $options = [])
    {
        self::$config = $options;
    }

    /**
     * Returns a configuration setting
     *
     * @param string $key The key of the config setting to return
     * @return string self::$config[$property]
     */
    public static function get($key)
    {
        $config = self::getAll();
        return $config[$key];
    }

    /**
     * Returns all configuration settings
     *
     * @return string[] self::$config
     */
    public static function getAll()
    {
        if (empty(self::$config)) {
            self::configure();
        }
        return self::$config;
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
    public static function getHomeDir()
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
     * Ensures that directory paths work in any system
     *
     * @param string $path A path to set the directory separators for
     * @return string
     */
    public static function fixDirectorySeparators($path)
    {
        $fixed_path = str_replace(
            ['/', '\\',],
            DIRECTORY_SEPARATOR,
            $path
        );
        return $fixed_path;
    }

    /**
     * Sets constants necessary for the proper functioning of Terminus
     *
     * @return void
     */
    private static function configure()
    {
        self::importEnvironmentVariables();
        self::$config['root'] = self::getTerminusRoot();
        self::$config['php'] = self::getPhpBinary();
        self::$config['script'] = self::getTerminusScript();

        $file_config = Yaml::parse(
            file_get_contents(self::$config['root'] . self::$config_path)
        );
        foreach ($file_config as $name => $setting) {
            $key = self::getKeyFromConstant($name);
            if (isset(self::$config[$key])) {
                continue;
            } elseif (defined($name)) {
                $setting = constant($name);
            } elseif (isset($_SERVER[$name]) && ($_SERVER[$name] != '')) {
                $setting = $_SERVER[$name];
            } elseif (getenv($name)) {
                $setting = getenv($name);
            }
            $value = self::replacePlaceholders($setting);
            self::ensureDirExists($name, $value);
            self::$config[$key] = $value;
        }
        date_default_timezone_set(self::get('time_zone'));
    }

    /**
     * Ensures a directory exists
     *
     * @param string $name  The name of the config var
     * @param string $value The value of the named config var
     * @return bool
     */
    private static function ensureDirExists($name, $value)
    {
        if ((strpos($name, 'TERMINUS_') !== false)
        && (strpos($name, '_DIR') !== false)
        && ($value != '~')
        ) {
            try {
                $dir_exists = (is_dir($value)
                || (!file_exists($value) && @mkdir($value, 0777, true)));
            } catch (\Exception $e) {
                return false;
            }
            return $dir_exists;
        }
        return null;
    }

    /**
     * Returns location of PHP with which to run Terminus
     *
     * @return string
     */
    private static function getPhpBinary()
    {
        if (isset(self::$config['php'])) {
            return self::$config['php'];
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
    private static function getTerminusRoot($current_dir = null)
    {
        if (isset(self::$config['root'])) {
            return self::$config['root'];
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
        $root_dir = self::getTerminusRoot($dir);
        return $root_dir;
    }

    /**
     * Finds and returns the name of the script running Terminus functions
     *
     * @return string
     */
    private static function getTerminusScript()
    {
        if (isset(self::$config['script'])) {
            return self::$config['script'];
        }
        if (defined('TERMINUS_SCRIPT')) {
            return TERMINUS_SCRIPT;
        }

        $debug           = debug_backtrace();
        $script_location = array_pop($debug);
        $script_name     = str_replace(
            self::$config['root'] . '/',
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
    private static function importEnvironmentVariables()
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
    private static function replacePlaceholders($string)
    {
        $regex = '~\[\[(.*?)\]\]~';
        preg_match_all($regex, $string, $matches);
        if (!empty($matches)) {
            foreach ($matches[1] as $id => $value) {
                $replacement_key = self::getKeyFromConstant(trim($value));
                if (isset(self::$config[$replacement_key])) {
                    $replacement = self::$config[$replacement_key];
                    $string = str_replace($matches[0][$id], $replacement, $string);
                }
            }
        }
        $fixed_string = self::fixDirectorySeparators(
            str_replace('~', self::getHomeDir(), $string)
        );
        return $fixed_string;
    }

    /**
     * Reflects a key name given a Terminus constant name
     *
     * @param string $constant_name The name of a constant to get a key for
     * @return string
     */
    private static function getKeyFromConstant($constant_name)
    {
        $key = strtolower(str_replace(self::$constant_prefix, '', $constant_name));
        return $key;
    }
}
