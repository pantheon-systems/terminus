<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SiteCommand.
 *
 * @package Pantheon\Terminus\Commands\Import
 */
class SiteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     *  Imports a site archive (code, database, and files) to the site.
     *
     * @authorize
     *
     * @command import:site
     * @aliases site:import
     *
     * @param string $site_name Site name
     * @param string $url Publicly accessible URL of the site archive
     *                    exported with drush 8 archive-dump command.
     *                    If you need to import a site in Drupal >=9 consider conversion:import-site command from https://github.com/pantheon-systems/terminus-conversion-tools-plugin
     * @usage <site_name> <url> Imports the site archive at <url> to <site_name>.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function import($site_name, $url)
    {
        $site = $this->getSiteById($site_name);
        $env = $site->getEnvironments()->get('dev');

        if (!$this->confirm(
            'Are you sure you overwrite the code, database and files for {env} on {site}?',
            ['site' => $site->getName(), 'env' => $env->getName()]
        )) {
            return;
        }

        try {
            $this->processWorkflow($env->import($url));
        } catch (\Exception $e) {
            if ($e->getMessage() == 'Successfully queued import_site') {
                throw new TerminusException('Site import failed');
            }
            throw $e;
        }
        $this->log()->notice('Imported site onto Pantheon');
    }
}
