<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

abstract class InfoBaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Get the Workflow object.
     */
    protected function getWorkflow($site_id, $options)
    {
        if (!($options['workflow-id'] || $options['latest-with-logs'])) {
            $this->log()->error('Required option missing: --workflow-id or --latest-with-logs.');
            return;
        }
        $site = $this->getSite($site_id);

        if ($options['workflow-id']) {
            $workflow_id = $options['workflow-id'];
            $model_data  = (object)['id' => $workflow_id];
            $workflow    = $site->workflows->add($model_data);
        } elseif ($options['latest-with-logs']) {
            $site->workflows->fetch(['paged' => false]);
            $workflow = $site->workflows->findLatestWithLogs();
            if (!$workflow) {
                $this->log()->info('No recent workflow has logs');
                return;
            }
        }
        try {
            $workflow->fetchWithLogs();
        } catch (\Exception $e) {
            $this->log()->error("Workflow was not found.");
            return;
        }
        return $workflow;
    }
}
