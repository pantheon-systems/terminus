<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays a list of the site's environments.
     *
     * @authorize
     *
     * @command env:list
     * @aliases envs
     *
     * @field-labels
     *     id: ID
     *     created: Created
     *     domain: Domain
     *     connection_mode: Connection Mode
     *     locked: Locked
     *     initialized: Initialized
     * @return RowsOfFields
     *
     * @param string $site_id Site name
     *
     * @usage <site>
     *    Displays a list of <site>'s environments.
     */
    public function listEnvs($site_id)
    {
        $site = $this->getSite($site_id);
        $data = [];
        foreach ($site->getEnvironments()->all() as $env) {
            $data[] = $env->serialize();
        }
        return new RowsOfFields($data);
    }
}
