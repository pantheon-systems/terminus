<?php


namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Models\Domain;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SetPrimaryCommand
 * @package Pantheon\Terminus\Commands\Domain
 */
class SetPrimaryCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Sets the primary domain for a site and environment, causing all traffic to redirect to the primary domain.
     *
     * @authorize
     *
     * @command domain:primary:set
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain A domain that has been associated to your site. Optional when running interactively.
     *
     * @usage domain:primary:set <site_env>
     */
    public function set($site_env, $domain)
    {
        /**
         * @var $site Site
         * @var $env Environment
         */
        list(, $env) = $this->getSiteEnv($site_env);

        // The primary domain is set via a workflow so as to use workflow logging to track changes.
        $this->log()->notice('Setting primary domain to {domain}...', ['domain' => $domain]);
        $workflow = $env->setPrimaryDomain($domain);
        $this->processWorkflow($workflow);
    }

    /**
     * Removes the primary domain for a site and environment.
     *
     * @authorize
     *
     * @command domain:primary:reset
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     */
    public function reset($site_env)
    {
        /**
         * @var $site Site
         * @var $env Environment
         */
        list(, $env) = $this->getSiteEnv($site_env);

        $this->log()->notice('Unsetting primary domain...');
        $workflow = $env->setPrimaryDomain(null);
        $this->processWorkflow($workflow);
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
     * @hook interact domain:primary:set
     */
    public function setInteract(InputInterface $input, OutputInterface $output, AnnotationData $annotationData)
    {
        $domain = $input->getArgument('domain');
        if (empty($domain)) {
            /**
             * @var $site Site
             * @var $env Environment
             */
            list($site, $env) = $this->getSiteEnv($input->getArgument('site_env'));
            $domains = $env->getDomains()->ids();
            // Put pantheonsite.io domains at the bottom.
            usort($domains, function ($a, $b) {
                if (preg_match('|\.pantheonsite\.io$|', $a)) {
                    $a = chr(255) . $a;
                }
                if (preg_match('|\.pantheonsite\.io$|', $b)) {
                    $b = chr(255) . $b;
                }
                return strcmp($a, $b);
            });
            if (!empty($domains)) {
                $domain = $this->io()->choice('Select the primary domain for this site', $domains);
                $input->setArgument('domain', $domain);
            }
        }
    }
}
