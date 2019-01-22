<?php

namespace Pantheon\Terminus\Config;

/**
 * Class TerminusConfig
 * @package Pantheon\Terminus\Config
 */
class TerminusConfig extends \Robo\Config\Config
{
    /**
     * @var string
     */
    protected $constant_prefix = 'TERMINUS_';

    /**
     * @var array
     */
    protected $sources = [];

    /**
     * @var string
     */
    protected $source_name = 'Unknown';

    /**
     * Replaces missing combine function
     *
     * @param $array
     * @return $this TerminusConfig
     */
    public function combine($data)
    {
        foreach ($data as $key => $val) {
            $this->set($key, $val);
        }
        return $this;
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
     * Override the values in this Config object with the given input Config
     *
     * @param \Pantheon\Terminus\Config\TerminusConfig $in
     */
    public function extend(TerminusConfig $in)
    {
        foreach ($in->keys() as $key) {
            $this->set($key, $in->get($key));
            // Set the source of this variable to make tracking config easier.
            $this->setSource($key, $in->getSource($key));
        }
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
     * Formats given Unix datetimes to the Terminus standard format given by the configuration
     *
     * @param string $datetime A Unix datetime to format
     * @return string Returns a formatted datetime
     */
    public function formatDatetime($datetime)
    {
        return date($this->get('date_format'), (integer)$datetime);
    }

    /**
     * Get a configuration value
     *
     * @param string $key Which config item to look up
     * @param string|null $defaultOverride Override usual default value with a different default
     *
     * @return mixed
     */
    public function get($key, $defaultOverride = null)
    {
        $value = parent::get($key, $defaultOverride);
        // Replace placeholders.
        if (is_string($value)) {
            $value = $this->replacePlaceholders($value);
        }
        return $value;
    }

    /**
     * Turn an internal key into a constant name
     *
     * @param string $key The key to get the constant name for.
     * @return string
     */
    public function getConstantFromKey($key)
    {
        $key = strtoupper($this->constant_prefix . $key);
        return $key;
    }

    /**
     * Get a description of where this configuration came from.
     *
     * @param $key
     * @return string
     */
    public function getSource($key)
    {
        return isset($this->sources[$key]) ? $this->sources[$key] : $this->getSourceName();
    }

    /**
     * Get the name of the source for this configuration object.
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->source_name;
    }

    /**
     * Return all of the keys in the Config
     * @return array
     */
    public function keys()
    {
        return array_keys($this->export());
    }

    /**
     * Formats some important data into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return [
            'php_binary_path'     => $this->get('php'),
            'php_version'         => $this->get('php_version'),
            'php_ini'             => $this->get('php_ini'),
            'project_config_path' => $this->get('config_dir'),
            'terminus_path'       => $this->get('root'),
            'terminus_version'    => $this->get('version'),
            'os_version'          => $this->get('os_version'),
        ];
    }

    /**
     * Set a config value. Converts key from Terminus constant (TERMINUS_XXX) if needed.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        // Convert Terminus constant name to internal key.
        if ($this->keyIsConstant($key)) {
            $key = $this->getKeyFromConstant($key);
        }
        return parent::set($key, $value);
    }

    /**
     * Reflects a key name given a Terminus constant name
     *
     * @param string $constant_name The name of a constant to get a key for
     * @return string
     */
    protected function getKeyFromConstant($constant_name)
    {
        $key = strtolower(str_replace($this->constant_prefix, '', $constant_name));
        return $key;
    }

    /**
     * Determines if a key is a Terminus constant name
     *
     * @param $key
     * @return boolean
     */
    protected function keyIsConstant($key)
    {
        return strpos($key, $this->constant_prefix) === 0;
    }

    /**
     * Exchanges values in [[ ]] in the given string with constants
     *
     * @param string $string The string to perform replacements on
     * @return string $string The modified string
     */
    protected function replacePlaceholders($string)
    {
        $regex = '~\[\[(.*?)\]\]~';
        preg_match_all($regex, $string, $matches);
        if (!empty($matches)) {
            foreach ($matches[1] as $id => $value) {
                $replacement_key = $this->getKeyFromConstant(trim($value));
                $replacement = $this->get($replacement_key);
                if ($replacement) {
                    $string = str_replace($matches[0][$id], $replacement, $string);
                }
            }
        }
        $fixed_string = $this->fixDirectorySeparators($string);
        return $fixed_string;
    }

    /**
     * Set the source for a given configuration item.
     *
     * @param $key
     * @param $source
     */
    protected function setSource($key, $source)
    {
        $this->sources[$key] = $source;
    }

    /**
     * @param mixed $source_name
     */
    protected function setSourceName($source_name)
    {
        $this->source_name = $source_name;
    }
}
