<?php

namespace Pantheon\Terminus\Commands\Import;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SiteCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class SiteCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

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
     *
     * @usage <site_name> <url> Imports the site archive at <url> to <site_name>.
     */
    public function import($site_name, $url)
    {
        list($site, $env) = $this->getSiteEnv($site_name, 'dev');

        $tr = ['site' => $site->getName(), 'env' => $env->getName()];
        if (!$this->confirm('Are you sure you overwrite the code, database and files for {env} on {site}?', $tr)) {
            return;
        }

        $workflow = $env->import($url);
        try {
            $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        } catch (\Exception $e) {
            if ($e->getMessage() == 'Successfully queued import_site') {
                throw new TerminusException('Site import failed');
            }
            throw $e;
        }
        $this->log()->notice('Imported site onto Pantheon');
    }
}
