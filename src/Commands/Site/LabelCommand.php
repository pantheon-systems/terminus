<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\Traits\WaitForWakeTrait;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class LabelCommand.
 *
 * @package Pantheon\Terminus\Commands\Site
 */
class LabelCommand extends SiteCommand
{
    use WorkflowProcessingTrait;
    use WaitForWakeTrait;

    /**
     * Creates a new site.
     *
     * @authorize
     *
     * @command site:label
     *
     * @param string $site_name Site name
     * @param string $label Site label
     *
     * @usage <site> <label> change the site label to the given value.
     * @usage <site> "<label>" Be sure to quote any label with spaces.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Exception
     */

    public function label($site_name, $label)
    {
        $workflow_options = [
            'label' => $label,
        ];
        return $this->processWorkflow(
            $this->session()->
                getUser()->
                getWorkflows()->
                create(
                    'set_site_label',
                    ['params' => $workflow_options,]
                )
        );
    }
}
