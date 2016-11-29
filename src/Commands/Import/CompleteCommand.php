<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CompleteCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class CompleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Inform Pantheon that your manual site importation has been completed
     *
     * @authorize
     *
     * @command import:complete
     * @aliases site:import:complete
     *
     * @param string $site_name Name of the site to mark as having completed importation
     *
     * @usage terminus import:complete <site>
     *   Marks the <site> site's import as complete
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
