<?php

namespace Pantheon\Terminus;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;

/**
 * Class SiteEnvSelector
 * @package Pantheon\Terminus
 */
class SiteEnvLookup implements ConfigAwareInterface, LoggerAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use LoggerAwareTrait;
    // Not used yet, but might want to check list of available sites, etc.
    use SessionAwareTrait;

    /**
     * Determine the site and environment that this command should target.
     * The Annotated Commands hook manager will call this function during
     * the init phase of any command that has a 'site-env' annotation.
     * If there is not already a commandline argument specifying the
     * site and environment, then determine an appropriate site and
     * environment to use, e.g. from environment variables, a .env file,
     * et. al., and insert it as an argument.
     *
     * Requirement: command's first parameter should be named $site_env_id.
     *
     * This hook works under the assumption that it is possible to
     * unambiguously determine whether or not the first parameter is
     * a $site_env_id parameter. If it is not, then the site and env are
     * looked up and inserted as the first parameter
     *
     * Note that we would want a different version of this hook to use
     * with commands that take only a site_id.
     *
     * @hook init @site-env
     */
    public function ensureSiteAndEnv($input, AnnotationData $annotationData)
    {
        // This hook only operates on commands with a $site_env_id parameter
        if (!$input->hasArgument('site_env_id')) {
            return;
        }

        // If the $site_env_id paramter is already set (indicates a
        // valid site and environment), then do nothing.
        $site_env_id = $input->getArgument('site_env_id');
        if ($this->isValidSiteEnvId($site_env_id)) {
            return;
        }

        $site_env_id = $this->determineSiteEnvId();
        $this->insertNewFirstArgument($input, $site_env_id);
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
     *
     * @hook init @site
     */
    public function ensureSite($input, AnnotationData $annotationData)
    {
        $arguments = $input->getArguments();
        $last = end($arguments);

        if (empty($last)) {
            $site_id = $this->determineSiteId($input);
            $this->insertNewFirstArgument($input, $site_id);
        }
    }

    /**
     * Determine if the provided $site_env_id is valid.
     */
    protected function isValidSiteEnvId($site_env_id)
    {
        return strpos($site_env_id, '.') !== false;
    }

    protected function determineSiteId()
    {
        // TODO: More robust site lookup; see determineSiteEnvId
        return getenv('TERMINUS_SITE');
    }

    /**
     * Determine the site and env to use with the current Terminus command.
     */
    protected function determineSiteEnvId()
    {
        // If TERMINUS_SITE and TERMINUS_ENV are set, then
        // return the site and env they indicate.
        $site = getenv('TERMINUS_SITE');
        $env = getenv('TERMINUS_ENV');
        if (!empty($site) && !empty($env)) {
            return "$site.$env";
        }

        // TODO: We could check for an .env file at the cwd

        // TODO: We could check for an .env file at the root directory
        // of the current git repository, if the cwd is inside of a
        // local working copy of a repository.
        //
        // Find the top-level directory:
        // git rev-parse --show-toplevel

        // TODO: We could also check to see if the cwd is inside
        // a local working copy of a Pantheon repository.
        //
        // Find the remote url for the remote 'origin':
        // git config --get remote.origin.url
        //
        // That returns something like:
        // ssh://codeserver.dev.ID@codeserver.dev.ID.drush.in:2222/~/repository.git
        //
        // If the url ends in '.drush.in:2222/~/repository.git', then
        // it is a Pantheon URL, and we could convert the site id
        // to the site name.
        //
        // To determine the environment, look at the current branch:
        // git rev-parse --abbrev-ref HEAD
        //
        // Convert 'master' to 'dev'.

        // If we cannot determine the site and environment
        // to operate on, return an empty id.  We can prompt
        // the user in the interact phase (but not here).
        return '';
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
