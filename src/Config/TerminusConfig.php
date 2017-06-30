<?php

namespace Pantheon\Terminus\Config;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class TerminusConfig
 * @package Pantheon\Terminus\Config
 */
class TerminusConfig extends \Robo\Config
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
     * Set add all the values in the array to this Config object.
    }
     * @param array $array
     */
    public function fromArray(array $array = [])
    {
        foreach ($array as $key => $val) {
            $this->set($key, $val);
        }
    }

    /**
     * Convert the config to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $out = [];
        foreach ($this->keys() as $key) {
            $out[$key] = $this->get($key);
        }
        return $out;
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
     * Return all of the keys in the Config
     * @return array
     */
    public function keys()
    {
        return array_keys($this->export());
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
     * Get the name of the source for this configuration object.
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->source_name;
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
     * @param mixed $source_name
     */
    public function setSourceName($source_name)
    {
        $this->source_name = $source_name;
    }
}
