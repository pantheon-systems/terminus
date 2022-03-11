<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;

/**
 * Class UpdatesCommand.
 *
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
abstract class UpdatesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Return the upstream for the given site
     *
     * @param \Pantheon\Terminus\Models\Environment $env
     *
     * @return object The upstream information
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getUpstreamUpdates($env)
    {
        if (empty($upstream = $env->getUpstreamStatus()->getUpdates())) {
            throw new TerminusException('There was a problem checking your upstream status. Please try again.');
        }
        return $upstream;
    }

    /**
     * Get the list of upstream updates for a site
     *
     * @param \Pantheon\Terminus\Models\Environment $env
     *
     * @return array The list of updates
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getUpstreamUpdatesLog($env)
    {
        $updates = $this->getUpstreamUpdates($env);
        return property_exists($updates, 'update_log') ? (array)$updates->update_log : [];
    }

    /**
     * Check upstream updates for given environment.
     *
     * @param \Pantheon\Terminus\Models\Environment $env.
     */
    protected function checkUpstreamUpdates($env)
    {
        if ($env->isBuildStepEnabled()) {
            $workflow = $env->getWorkflows()->create('check_upstream_updates');
            $this->processWorkflow($workflow);
        }
    }

    /**
     * Get the list of composer dependency updates for a site environment
     *
     * @param \Pantheon\Terminus\Models\Environment $env
     *
     * @return array The list of updates
     */
    protected function getComposerUpdatesLog($env)
    {
        // Check if the site is IC-enabled.
        if (empty($env->isBuildStepEnabled())) {
            return [];
        }
        $updates = $env->getUpstreamStatus()->getComposerUpdates();
        if (empty($updates)) {
            return [];
        }
        $deps = [];
        if (!empty($updates->added_dependencies)) {
            $deps = array_merge($deps, $updates->added_dependencies);
        }
        if (!empty($updates->updated_dependencies)) {
            $deps = array_merge($deps, $updates->updated_dependencies);
        }
        if (!empty($updates->removed_dependencies)) {
            $deps = array_merge($deps, $updates->removed_dependencies);
        }
        return $deps;
    }
}
