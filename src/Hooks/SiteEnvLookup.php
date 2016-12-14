<?php

namespace Pantheon\Terminus\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SiteEnvLookup
 * @package Pantheon\Terminus
 */
class SiteEnvLookup implements ConfigAwareInterface, LoggerAwareInterface, SiteAwareInterface
{
    use ConfigAwareTrait;
    use LoggerAwareTrait;
    use SiteAwareTrait;

    /**
     * Determine the site and environment that this command should target.
     * The Annotated Commands hook manager will call this function during
     * the init phase of any command that has a 'site-env' annotation.
     * If there is not already a commandline argument specifying the
     * site and environment, then determine an appropriate site and
     * environment to use, e.g. from environment variables, a .env file,
     * et. al., and insert it as an argument.
     *
     * Requirement: command's first parameter should be named $site_env.
     *
     * This hook works under the assumption that it is possible to
     * unambiguously determine whether or not the first parameter is
     * a $site_env parameter. If it is not, then the site and env are
     * looked up and inserted as the first parameter
     *
     * Note that we would want a different version of this hook to use
     * with commands that take only a site_id.
     *
     * @hook init *
     */
    public function siteAndEnvLookupHook($input, AnnotationData $annotationData)
    {
        // For commands with a $site_env parameter, inject the site and
        // environment if it has not already been defined.
        if ($input->hasArgument('site_env')) {
            return $this->ensureSiteAndEnv($input, $annotationData);
        }
        // For commands with a $site_env parameter, inject the site and
        // environment if it has not already been defined.
        if ($input->hasArgument('site')) {
            return $this->ensureSite($input, $annotationData);
        }
    }

    public function ensureSiteAndEnv($input, AnnotationData $annotationData)
    {
        // If the $site_env paramter is already set (indicates a
        // valid site and environment), then do nothing.
        $site_env = $input->getArgument('site_env');
        if ($this->isValidSiteEnv($site_env) || $this->hasAllParameters($input)) {
            return;
        }

        $site_env = $this->determineSiteEnv();
        if (empty($site_env)) {
            return;
        }

        $this->insertNewFirstArgument($input, $site_env);
    }

    /**
     * Determine the site that this command should target.
     * The Annotated Commands hook manager will call this function during
     * the init phase of any command that has a 'site-env' annotation.
     *
     * This hook works under the assumption that the command takes
     * a fixed number of parameters.  If the last parameter is not
     * provided, then the site id is looked up and inserted.
     *
     * Note that this is not necessarily a good assumption; if the user
     * mistakenly omitted a parameter, but did provide a site id, then
     * the resulting error message would be strange.  It would be
     * better if it were possible to unambiguously determine if the
     * first parameter is a valid site id.
     */
    public function ensureSite($input, AnnotationData $annotationData)
    {
        if ($this->hasAllParameters($input)) {
            return;
        }

        $site_id = $this->determineSite($input);
        $this->insertNewFirstArgument($input, $site_id);
    }

    /**
     * Determine whether the user already provided all arguements
     * for this command. If not all arguments were provided, then
     * it remains a possibility that we may be able to insert a
     * new first argument (site.env or site)
     */
    protected function hasAllParameters($input)
    {
        $arguments = $input->getArguments();
        $last = end($arguments);

        // If the last parameter does not have a value,
        // then not all parameters were supplied. If the last
        // argument is an array, then variable arguments are
        // supported, so we will always be able to add more.
        if (empty($last) || is_array($last)) {
            return false;
        }

        // All parameters have values already.
        return true;
    }

    /**
     * Determine if the provided $site_env is valid.
     */
    protected function isValidSiteEnv($site_env)
    {
        return strpos($site_env, '.') !== FALSE;
    }

    protected function determineSite()
    {
        // If TERMINUS_SITE and TERMINUS_ENV are set, then
        // return the site and env they indicate. The config
        // will also be loaded from a .env file at the cwd if present.
        $site = $this->getConfig()->get('site');
        if (!empty($site)) {
            return $site;
        }

        // Check the url of the origin of the repo at the cwd
        list($site, $env) = $this->siteAndEnvFromRepo();
        if (!empty($site)) {
            return $site;
        }
        return '';
    }

    /**
     * Determine the site and env to use with the current Terminus command.
     */
    protected function determineSiteEnv()
    {
        // If TERMINUS_SITE and TERMINUS_ENV are set, then
        // return the site and env they indicate. The config
        // will also be loaded from a .env file at the cwd if present.
        $site = $this->getConfig()->get('site');
        $env = $this->getConfig()->get('env');
        if (!empty($site) && !empty($env)) {
            return "$site.$env";
        }

        // Check the url of the origin of the repo at the cwd
        list($site, $env) = $this->siteAndEnvFromRepo();
        if (!empty($site) && !empty($env)) {
            return "$site.$env";
        }
        return '';
    }

    protected function siteAndEnvFromRepo()
    {
        $repo_url = exec('git config --get remote.origin.url');
        if (!preg_match('#ssh://[^@]*@codeserver\.[^.]*\.([^.]*)\.drush\.in:2222/~/repository\.git#', $repo_url, $matches)) {
            return ['',''];
        }

        $site_id = $matches[1];
        $site = $this->getSite($site_id);

        // Get the current branch
        $env = exec('git rev-parse --abbrev-ref HEAD');
        if ($env == 'master') {
            $env = 'dev';
        }

        return [$site->getName(), $env];
    }

    /**
     * Insert the provided value into the argument list as the
     * new first parameter, shifting all others down.
     */
    protected function insertNewFirstArgument($input, $newFirstArg)
    {
        // Get all of the arguments. Remove the first one ('command').
        $arguments = $input->getArguments();
        array_shift($arguments);

        $newArgValue = $newFirstArg;
        foreach ($arguments as $key => $value) {
            // Only the last argument should ever be an array -- and the
            // last always should be an array.
            if (is_array($value)) {
                array_unshift($value, $newArgValue);
                $input->setArgument($key, $value);
                return;
            }
            else {
                $input->setArgument($key, $newArgValue);
                $newArgValue = $value;
            }
        }
    }
}
