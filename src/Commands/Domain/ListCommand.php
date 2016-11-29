<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Domain
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List the domains attached to an environment
     *
     * @authorize
     *
     * @command domain:list
     * @alias domains
     *
     * @field-labels
     *   domain: Domain
     *   dns_zone_name: DNS Zone Name
     *   key: Key
     *   deletable: Is Deletable
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment to list the attached domains of, in the form `site-name.env`.
     *
     * @usage terminus domain:list <site>.<env>
     *     Lists the domains attached to the <site> site's <env> environment
     */
    public function listDomains($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $domains = array_map(
            function ($domain) {
                return $domain->serialize();
            },
            $env->getDomains()->all()
        );
        return new RowsOfFields($domains);
    }
}
