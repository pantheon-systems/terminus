<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class CodeLogCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CodeLogCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the code log for the environment.
     *
     * @authorize
     *
     * @command env:code-log
     *
     * @field-labels
     *     datetime: Timestamp
     *     author: Author
     *     labels: Labels
     *     hash: Commit ID
     *     message: Message
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays the code log for <site>'s <env> environment.
     */
    public function codeLog($site_env)
    {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'dev');
        return new RowsOfFields($env->getCommits()->serialize());
    }
}
