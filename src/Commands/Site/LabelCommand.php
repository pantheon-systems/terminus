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
     * Changes the site label
     *
     * @authorize
     *
     * @command site:label:set
     * @alias set-label
     * @alias slbl
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
        $siteObj = $this->sites()->get($site_name);
        return $this->processWorkflow(
            $siteObj->
                getWorkflows()->
                create(
                    'set_site_label',
                    ['params' => [
                        'label' => $label,
                    ]]
                )
        );
    }
}
