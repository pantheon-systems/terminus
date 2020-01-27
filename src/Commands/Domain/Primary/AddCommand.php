<?php


namespace Pantheon\Terminus\Commands\Domain\Primary;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Domain\Primary
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    const PLATFORM_DOMAIN = '.pantheonsite.io';

    /**
     * Sets a domain associated to the environment as primary, causing all traffic to redirect to it.
     *
     * @authorize
     *
     * @command domain:primary:add
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain A domain that has been associated to your site. Optional when running interactively.
     *
     * @usage <site>.<env> <domain> Designates <domain> as the primary domain of <site>'s <env> environment.
     */
    public function add($site_env, $domain)
    {
        /**
         * @var $site Site
         * @var $env Environment
         */
        list($site, $env) = $this->getSiteEnv($site_env);

        // The primary domain is set via a workflow so as to use workflow logging to track changes & update policy docs.
        $workflow = $env->getPrimaryDomainModel()->setPrimaryDomain($domain);
        $this->processWorkflow($workflow);
        $this->log()->notice(
            'Set {domain} as primary for {site}.{env}',
            ['domain' => $domain, 'site' => $site->get('name'), 'env' => $env->id,]
        );
    }

    /**
     * Prompt the user for the domain, if it was not specified.
     *
     * n.b. This hook is not called in --no-interaction mode.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param AnnotationData $annotationData
     *
     * @hook interact domain:primary:add
     */
    public function interact(InputInterface $input, OutputInterface $output, AnnotationData $annotationData)
    {
        $domain = $input->getArgument('domain');
        if (empty($domain)) {
            /**
             * @var $site Site
             * @var $env Environment
             */
            list($site, $env) = $this->getSiteEnv($input->getArgument('site_env'));
            $domains = $this->filterPlatformDomains($env->getDomains()->ids());
            sort($domains);

            if (!empty($domains)) {
                $domain = $this->io()->choice('Select the primary domain for this site', $domains);
                $input->setArgument('domain', $domain);
            }
        }
    }

    /**
     * Filters strings ending in the platform domain from an array.
     *
     * @param $domains
     * @return array
     */
    public function filterPlatformDomains($domains)
    {
        return array_filter($domains, function ($domain) {
            return substr_compare($domain, self::PLATFORM_DOMAIN, -strlen(self::PLATFORM_DOMAIN)) !== 0;
        });
    }
}
