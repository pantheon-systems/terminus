<?php

namespace Pantheon\Terminus\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class SiteEnvLookup
 * @package Pantheon\Terminus
 */
class SiteEnvLookup implements ConfigAwareInterface, SiteAwareInterface
{
    use ConfigAwareTrait;
    use SiteAwareTrait;

    /**
     * Determine the site and environment that this command should target.
     * The Annotated Commands hook manager will call this function during
     * the init phase of all commands. For those commands that have a
     * parameter  'site_env' or 'site', this hook will attempt to fill in
     * a default value for that parameter if the user did not provide it
     * on the command line.
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
        // For commands with a $site parameter, inject the site
        // if it has not already been defined.
        if ($input->hasArgument('site')) {
            return $this->ensureSite($input, $annotationData);
        }
    }

    /**
     * If the user did not provide a site_env paramter, then look
     * up a value to use from environment variables, .env file or
     * information from the current git repository.
     */
    protected function ensureSiteAndEnv($input, AnnotationData $annotationData)
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
     * Works like ensureSiteAndEnv, but is used for commands that
     * take just a 'site' parameter.
     */
    protected function ensureSite($input, AnnotationData $annotationData)
    {
        if ($this->hasAllParameters($input)) {
            return;
        }

        $site_id = $this->determineSite();
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
        return strpos($site_env, '.') !== false;
    }

    /**
     * Look up the site to use with the current Terminus command.
     */
    protected function determineSite()
    {
        // If TERMINUS_SITE is set, then return the site indicated.
        // The config will also be loaded from a .env file at the cwd
        // if present.
        $site = $this->getConfig()->get('site');
        if (!empty($site)) {
            return $site;
        }

        // Check the url of the origin of the repo at the cwd
        list($site,) = $this->siteAndEnvFromRepo();
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

    /**
     * Examine the url of the current git repository, and return a site
     * and environment to use based on that information.
     */
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
            } else {
                $input->setArgument($key, $newArgValue);
                $newArgValue = $value;
            }
        }
    }
}
