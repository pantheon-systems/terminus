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
     * List a site's environments
     *
     * @authorize
     *
     * @command env:list
     * @aliases envs
     *
     * @field-labels
     *   id: ID
     *   created: Created
     *   domain: Domain
     *   connection_mode: Connection Mode
     *   locked: Locked
     *   initialized: Initialized
     * @return RowsOfFields
     *
     * @param string $site_id The site to list the environments of
     *
     * @usage env:list <site>
     *    Lists all environments for the site named <site>
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
