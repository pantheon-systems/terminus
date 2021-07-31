<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Domain
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Displays domains associated with the environment.
     *
     * @authorize
     * @filter-output
     *
     * @command domain:list
     * @aliases domains
     *
     * @field-labels
     *     id: Domain/ID
     *     type: Type
     *     primary: Is Primary
     *     deletable: Is Deletable
     *     status: status
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays domains associated with <site>'s <env> environment.
     */
    public function listDomains($site_env)
    {
        [$site, $env] = explode('.', $site_env);
        if (empty($site) || empty($env)) {
            throw new TerminusNotFoundException(
                'The Site and environment must take the form of {site}.{env} followed by the domain name you are adding'
            );
        }
        $env = $this->sites()->get($site)->getEnvironments()->get($env) ?? null;
        if (!$env instanceof Environment) {
            throw new TerminusNotFoundException(
                'Site/env not found {site}.{env}',
                ['site' => $site, 'env' => $env]
            );
        }
        return $this->getRowsOfFields($env->getDomains());
    }
}
