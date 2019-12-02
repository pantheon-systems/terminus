<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Symfony\Component\Filesystem\Filesystem;

class DrushSitesYmlEmitter implements AliasEmitterInterface
{
    protected $base_dir;
    protected $home;

    public function __construct($base_dir, $home, $target_name = 'pantheon')
    {
        $this->base_dir = $base_dir;
        $this->home = $home;
        $this->target_name = $target_name;
    }

    /**
     * {@inheritdoc}
     */
    public function notificationMessage()
    {
        $pantheon_sites_dir = $this->pantheonSitesDir();

        return 'Writing Drush 9 alias files to ' . $pantheon_sites_dir;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $alias_replacements)
    {
        $pantheon_sites_dir = $this->pantheonSitesDir();

        $fs = new Filesystem();
        $fs->mkdir($pantheon_sites_dir);

        foreach ($alias_replacements as $name => $replacements) {
            $alias_file_contents = $this->getAliasFragment($replacements);
            file_put_contents("{$pantheon_sites_dir}/{$name}.site.yml", $alias_file_contents);
        }

        // Add in our directory location to the Drush alias file search path
        $drushYmlEditor = new DrushYmlEditor($this->base_dir);
        $drushConfig = $drushYmlEditor->getDrushConfig();
        if (isset($drushConfig['drush']['paths']['alias-path'])) {
            $drushConfig['drush']['paths']['alias-path'] =
                array_filter($drushConfig['drush']['paths']['alias-path'], array($this, 'filterForSites'));
        }
        if (isset($drushConfig['drush']['paths']['include'])) {
            $drushConfig['drush']['paths']['include'] =
                array_filter($drushConfig['drush']['paths']['include'], array($this, 'filterForSites'));
        }
        $drushConfigFiltered['drush']['paths']['alias-path'][] = '${env.home}/.drush/sites';
        $drushConfigFiltered['drush']['paths']['alias-path'][] =
            str_replace($this->home, '${env.home}', $pantheon_sites_dir);
        $drushConfigFiltered['drush']['paths']['include'][] = '${env.home}/.drush/pantheon';
        $drushYmlEditor->writeDrushConfig($drushConfigFiltered);

        //copy policy docs
        $policyFromPath = 'policy/Commands/PantheonAliasPolicyCommands.php';
        $policyToPath = $this->base_dir . "/pantheon/Commands";
        $fs = new Filesystem();
        if (!file_exists($policyToPath)) {
            $fs->mkdir($policyToPath);
        }
        $policyTemplate = new Template();
        $copied = $policyTemplate->copy($policyFromPath, $policyToPath);
    }

    /**
     * Determine which lines should be removed when rewriting Drush config file.
     *
     * @param string $line
     * @return bool
     */
    protected function filterForSites($line)
    {
        if ((strpos($line, 'pantheon') !== false) || (strpos($line, '/.drush/sites') !== false)) {
            return false;
        }
        return true;
    }

    /**
     * Return the data for one alias record, and run the replacements on it.
     *
     * @param array $replacements
     * @return string
     */
    protected function getAliasFragment(array $replacements)
    {
        return Template::process('fragment.site.yml.tmpl', $replacements);
    }

    /**
     * Return the path to the sites aliases directory.
     *
     * @return string
     */
    protected function pantheonSitesDir()
    {
        return $this->base_dir . '/sites/' . $this->target_name;
    }
}
