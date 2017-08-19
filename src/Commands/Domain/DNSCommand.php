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
     *     value: Recommended Value
     *     detected_value: Detected Value
     *     status: Status
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
            $recommendations = $domain->get('dns_status_details')->dns_records;
            foreach ($recommendations as $recommendation) {
                $settings[] = [
                    'name' => $domain->id,
                    'type' => $recommendation->type,
                    'value' => $recommendation->target_value,
                    'detected_value' => $recommendation->detected_value,
                    'status' => $recommendation->status,
                ];
            }
        }
        return new RowsOfFields($settings);
    }
}
