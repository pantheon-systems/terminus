<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DNSCommand
 * @package Pantheon\Terminus\Commands\Domain
 */
class DNSCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays recommended DNS settings for the environment.
     *
     * @authorize
     *
     * @command domain:dns
     *
     * @field-labels
     *     name: Name
     *     type: Record Type
     *     value: Value
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays recommended DNS settings for <site>'s <env> environment.
     */
    public function getRecommendations($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $domains = $env->getDomains()->setHydration('recommendations')->all();
        $settings = [];
        foreach ($domains as $domain) {
            $settings = array_merge(
                $settings,
                array_map(
                    function ($recommendation) use ($domain) {
                        $recommendation->name = $domain->id;
                        return (array)$recommendation;
                    },
                    $domain->get('dns_recommendations')
                )
            );
        }
        return new RowsOfFields($settings);
    }
}
