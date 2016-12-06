<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays HTTPS information about an environment.
     *
     * @authorize
     *
     * @command https:info
     *
     * @field-labels
     *    enabled: Enabled?
     *    ipv4: IPV4
     *    ipv6: IPV6
     * @return PropertyList
     *
     * @param string $site_env Site and environment in the form `site-name.env`
     *
     * @usage terminus https:info <site>.<env>
     *     Displays information about the HTTPS status of <site>'s <env> environment.
     */
    public function info($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $https_balancers = array_filter(
            $env->getLoadbalancers()->all(),
            function ($loadbalancer) {
                return $loadbalancer->isSSL();
            }
        );
        if (empty($https_balancers)) {
            return new PropertyList(['enabled' => 'false', 'ipv4' => null, 'ipv6' => null,]);
        }
        $https = array_shift($https_balancers);
        $https_info = array_merge(['enabled' => 'true'], $https->serialize());
        return new PropertyList($https_info);
    }
}
