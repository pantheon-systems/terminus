<?php

namespace Pantheon\Terminus\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Walks up from a starting directory to find a .terminus config file.
 */
class DirectoryConfig extends TerminusConfig
{
    /**
     * @param string $startDir Directory to start searching from (typically getcwd()).
     */
    public function __construct(string $startDir)
    {
        parent::__construct();

        $dir = $startDir;
        while (true) {
            $candidate = $dir . DIRECTORY_SEPARATOR . '.terminus';
            if (file_exists($candidate) && is_file($candidate)) {
                $this->setSourceName($candidate);
                $data = Yaml::parse(file_get_contents($candidate)) ?? [];
                $this->combine($data);
                break;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                // Reached the filesystem root without finding a .terminus file.
                break;
            }
            $dir = $parent;
        }
    }
}
