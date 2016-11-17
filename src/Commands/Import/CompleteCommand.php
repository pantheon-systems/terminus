<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class CompleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Informs Pantheon that your manual site importation has been completed
     *
     * @authorized
     *
     * @command import:complete
     * @aliases site:import:complete
     *
     * @option string $site_name Name of the site to mark as having completed importation
     * @usage terminus import:complete <site_name>
     *   Marks the <site_name> site's import as complete
     */
    public function complete($site_name)
    {
        $site = $this->sites->get($site_name);
        $workflow = $site->completeMigration();
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice('The import of {site} has been marked as complete.', ['site' => $site->get('name'),]);
    }
}
