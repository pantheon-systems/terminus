<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List a site's environments
     *
     * @authorized
     *
     * @command env:list
     *
     * @field-labels
     *   id: ID
     *   created: Created
     *   domain: Domain
     *   connection_mode: Connection Mode
     *   locked: Locked
     *   initialized: Initialized
     *
     * @param string $site_id The site to the environments for
     *
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
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
