<?php

namespace Pantheon\Terminus\Config;

/**
 * Class DotEnvConfig
 * @package Pantheon\Terminus\Config
 */
class DotEnvConfig extends TerminusConfig
{
    /**
     * @var string
     */
    protected $file;

    /**
     * DotEnvConfig constructor.
     */
    public function __construct($dir)
    {
        parent::__construct();

        $file = $dir . '/.env';
        $this->setSourceName($file);

        // Load environment variables from __DIR__/.env
        // TODO: If there is no .env at the cwd, we could use
        // 'git rev-parse --show-toplevel' to determine if there is
        // a .env file at the root of the current project.
        if (file_exists($file)) {
            // Remove comments (which start with '#')
            $lines = file($file);
            $lines = array_filter($lines, function ($line) {
                return strpos(trim($line), '#') !== 0;
            });
            $info = $this->parse($lines);
            $this->combine($info);
        }
    }

    /**
     * parse reads the provided list of lines in 'ini' format
     * (key=value) and returns an associative array of key/value pairs.
     * Similar to 'parse_ini_string', but not as fragile.
     *
     * @param string[] $lines A list of lines
     * @return string[] An associative array
     */
    protected function parse($lines)
    {
        $info = [];

        foreach ($lines as $line) {
            list($key, $value) = array_pad(explode('=', trim($line)), 2, '');
            if (!empty($key)) {
                $info[$key] = $this->trimQuotes($value);
            }
        }
        return $info;
    }

    /**
     * trimQuotes returns the provided string without any wrapping
     * quotation characters.
     */
    protected function trimQuotes($value)
    {
        if (!empty($value) && ($value[0] === $value[strlen($value) - 1])) {
            return trim($value, "'\"");
        }
        return $value;
    }
}
