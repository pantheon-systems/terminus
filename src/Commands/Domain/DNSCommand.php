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
     *     domain: Domain
     *     type: Record Type
     *     value: Recommended Value
     *     detected_value: Detected Value
     *     status: Status
     *     status_message: Status Message
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays recommended DNS settings for <site>'s <env> environment.
     */
    public function getRecommendations($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $domains = $env->getDomains()->filter(
            function ($domain) {
                return $domain->get('type') === 'custom';
            }
        )->all();
        $settings = [];
        foreach ($domains as $domain) {
            $settings = array_merge($settings, $domain->getDNSRecords()->serialize());
        }
        return new RowsOfFields($settings);
    }
}
