<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Lists the domains attached to an environment
     *
     * @command domain:list
     * @alias domains
     *
     * @param string $site_env Site & environment to list the attached domains of, in the form `site-name.env`.
     * @return RowsOfFields
     *
     * @field-labels
     *   domain: Domain
     *   dns_zone_name: DNS Zone Name
     *   key: Key
     *   deletable: Is Deletable
     *
     * @usage terminus domain:list <site_name>.<env_id>
     *     Lists the domains attached to the <site name> site's <env_id> environment
     */
    public function listDomains($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $domains = array_map(
            function ($hostname) {
                return $hostname->serialize();
            },
            $env->hostnames->all()
        );
        return new RowsOfFields($domains);
    }
}
