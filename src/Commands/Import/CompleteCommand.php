<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CompleteCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class CompleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Finalizes the Pantheon import process.
     *
     * @authorize
     *
     * @command import:complete
     * @aliases site:import:complete
     *
     * @param string $site_name Site name
     *
     * @usage <site> Finalizes <site>'s Pantheon import process.
     */
    public function complete($site_name)
    {
        $site = $this->sites->get($site_name);
        $this->processWorkflow($site->completeMigration());
        $this->log()->notice('The import of {site} has been marked as complete.', ['site' => $site->get('name'),]);
    }
}
