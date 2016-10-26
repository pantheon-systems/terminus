<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DNSCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the recommended DNS settings for this environment
     *
     * @command domain:dns
     *
     * @param string $site_env Site & environment to get DNS settings for, in the form `site-name.env`.
     * @return RowsOfFields
     *
     * @field-labels
     *   type: Record Type
     *   value: Value
     *
     * @usage terminus domain:dns <site_name>.<env_id>
     *     Displays the recommended DNS settings for the <site_name> site's <env_id> environment
     */
    public function getRecommendations($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $settings = array_map(
            function ($domain) {
                return (array)$domain->get('dns_recommendations')[0];
            },
            $env->hostnames->setHydration('recommendations')->all()
        );
        return new RowsOfFields($settings);
    }
}
