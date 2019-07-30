<?php

/**
 * @file
 */

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Consolidation\Comments\Comments;
use Symfony\Component\Yaml\Yaml;

class DrushYmlEditor
{
    protected $dir;
    protected $comments;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Return the path to the drush.yml file.
     */
    public function getDrushYmlPath()
    {
        return $this->dir . "/drush.yml";
    }

    /**
     * Load the drush.yml file and return its parsed contents.
     */
    public function getDrushConfig()
    {
        $drushYmlPath = $this->getDrushYmlPath();

        // Load the drush.yml file
        if (file_exists($drushYmlPath)) {
            $drushYmlContents = file_get_contents($drushYmlPath);
        } else {
            $drushYmlContents = Template::load('initial.drush.yml');
        }
        $drushYml = Yaml::parse($drushYmlContents);
        $this->comments = new Comments();
        $this->comments->collect(explode("\n", $drushYmlContents));
        return $drushYml;
    }

    /**
     * Write a modified drush.yml file back to disk.
     */
    public function writeDrushConfig($drushYml)
    {
        $drushYmlPath = $this->getDrushYmlPath();
        $drushYml = Yaml::dump($drushYml, PHP_INT_MAX, 2);
        $drushYmlLines = $this->comments->inject(explode("\n", $drushYml));
        $drushYmlText = implode("\n", $drushYmlLines);

        return file_put_contents($drushYmlPath, $drushYmlText);
    }
}
