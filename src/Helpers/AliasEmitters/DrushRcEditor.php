<?php

/**
 * @file
 *
 */

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Consolidation\Comments\Comments;
use Symfony\Component\Yaml\Yaml;

class DrushRCEditor
{
    protected $dir;
    protected $comments;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Return the path to the drushrc.php file.
     */
    public function getDrushRCPath()
    {
        return $this->dir . "/drushrc.php";
    }

    /**
     * Load the drushrc.php file and return its parsed contents.
     */
    public function getDrushConfig()
    {
        $drushRCPath = $this->getDrushRCPath();
        // Load the drushrc.php file
        if (file_exists($drushRCPath)) {
            $drushRCContents = file_get_contents($drushRCPath);
        } else {
            $drushRCContents = '<?php' . "\n";
            //$newFile = fopen($this->getDrushRCPath(), "w");
        }
        $drushRCContents = explode("\n", $drushRCContents);
        return $drushRCContents;
    }

    /**
     * Write a modified drushrc.php file back to disk.
     */
    public function writeDrushConfig($drushRCText)
    {
        $drushRCPath = $this->getDrushRCPath();
        return file_put_contents($drushRCPath, $drushRCText);
    }
}
