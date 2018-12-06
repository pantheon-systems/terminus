<?php

namespace Pantheon\Terminus\Commands\Import;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CompleteCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class CompleteCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

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
        $workflow = $site->completeMigration();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice('The import of {site} has been marked as complete.', ['site' => $site->get('name'),]);
    }
}
