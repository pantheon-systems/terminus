<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Symfony\Component\Filesystem\Filesystem;

class AliasesDrushRcEmitter extends AliasesDrushRcBase
{
    protected $location;

    /**
     * AliasesDrushRcEmitter consturctor
     *
     * @param string $location
     * @param string $base_dir
     */
    public function __construct($location, $base_dir)
    {
        $this->location = $location;
        $this->base_dir = $base_dir;
    }

    /**
     * {@inheritdoc}
     */
    public function notificationMessage()
    {
        return 'Writing Drush 8 alias file to ' . $this->location;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $alias_replacements)
    {
        $alias_file_contents = $this->getAliasContents($alias_replacements);

        $fs = new Filesystem();
        $fs->mkdir(dirname($this->location));

        file_put_contents($this->location, $alias_file_contents);

        // Add in our directory location to the Drush alias file search path
        $drushRCEditor = new DrushRcEditor($this->base_dir);
        $drushConfig = $drushRCEditor->getDrushConfig();
        $drushConfigFiltered = implode("\n", array_filter($drushConfig, array($this, 'filterForPantheon')));
        $drushConfigFiltered .= "\n" . '$options["include"][] = drush_server_home() . "/.drush/pantheon/drush8";';
        $drushRCEditor->writeDrushConfig($drushConfigFiltered);

        //copy policy docs
        $policyFromPath = 'policy/drush8/pantheon_policy.drush.inc';
        $policyToPath = $this->base_dir . "/pantheon/drush8";
        $fs = new Filesystem();
        if (!file_exists($policyToPath)) {
            $fs->mkdir($policyToPath);
        }
        $policyTemplate = new Template();
        $copied = $policyTemplate->copy($policyFromPath, $policyToPath);
    }

    /**
     * Determine which lines should be removed when re-writing Drush config file.
     *
     * @return bool
     */
    protected function filterForPantheon($line)
    {
        if (strpos($line, '.drush/pantheon/drush8') !== false) {
            return false;
        }
        return true;
    }
}
