<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand.
 *
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
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
     *
     * @usage <site>.<env> Displays domains associated with <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listDomains($site_env)
    {
        $env = $this->getEnv($site_env);

        return $this->getRowsOfFields($env->getDomains());
    }
}
