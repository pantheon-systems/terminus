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
     * Displays domains associated with the environment.
     *
     * @authorize
     *
     * @command domain:list
     * @aliases domains
     *
     * @field-labels
     *     id: Domain/ID
     *     type: Type
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
        list(, $env) = $this->getSiteEnv($site_env);
        return new RowsOfFields($env->getDomains()->serialize());
    }
}
