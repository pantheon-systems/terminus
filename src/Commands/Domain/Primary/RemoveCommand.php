<?php


namespace Pantheon\Terminus\Commands\Domain\Primary;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Removes the primary designation from the primary domain in the site and environment.
     *
     * @authorize
     *
     * @command domain:primary:remove
     * @aliases domain:primary:rm
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Un-designates the primary domain of <site>'s <env> environment.
     */
    public function remove($site_env)
    {
        /**
         * @var $site Site
         * @var $env Environment
         */
        list($site, $env) = $this->getSiteEnv($site_env);

        $workflow = $env->getPrimaryDomainModel()->removePrimaryDomain();
        $this->processWorkflow($workflow);
        $this->log()->notice(
            'Primary domain has been removed from {site}.{env}',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
