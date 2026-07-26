<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Helpers\AliasEmitters\AliasesDrushRcEmitter;
use Pantheon\Terminus\Helpers\AliasEmitters\PrintingEmitter;
use Pantheon\Terminus\Helpers\AliasEmitters\DrushSitesYmlEmitter;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Generate lots of aliases
 */
class AliasesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use ConfigAwareTrait;

    /**
     * Generates Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     * Note that Drush 9 does not read alias files from global locations. You must set valid alias locations in your drush.yml file.
     * Refer to https://docs.pantheon.io/guides/drush/drush-aliases#manage-available-site-aliases-lists for more information.
     *
     * @authorize
     * @interact
     *
     * @command aliases
     * @aliases drush:aliases
     *
     * @option boolean $print Print aliases only (Drush 8 format)
     * @option string $location Path and filename for php aliases.
     * @option boolean $all Include all sites available, including team memberships.
     * @option string $only Only generate aliases for sites in the specified comma-separated list. This option is only recommended for use in CI scripts.
     * @option string $type Type of aliases to create: 'php', 'yml' or 'all'.
     * @option string $base Base directory to write .yml aliases.
     * @option string $target Base name to use to generate path to alias files.
     * @option boolean $custom-domains Use custom domains instead of platform domains for dev, test, and live environments. Uses primary domain if set, otherwise the first custom domain, falling back to platform domain if none configured.
     * @option boolean $db-url Obsolete option included to preserve backwards compatibility. No longer needed.
     *
     * @return string|null
     *
     * @usage Saves Pantheon Drush aliases for sites on which the currently logged-in user is on the team to ~/.drush/pantheon.aliases.drushrc.php.
     * @usage --print Displays Pantheon Drush 8 aliases for sites on which the currently logged-in user is on the team.
     * @usage --location=<full_path> Saves Pantheon Drush 8 aliases for sites on which the currently logged-in user is on the team to <full_path>.
     */
    public function aliases($options = [
        'print' => false,
        'location' => null,
        'all' => false,
        'only' => '',
        'type' => 'all',
        'base' => '~/.drush',
        'db-url' => true,
        'target' => 'pantheon',
        'custom-domains' => false,
    ])
    {
        // Be forgiving about the spelling of 'yaml'
        if ($options['type'] == 'yaml') {
            $options['type'] = 'yml';
        }

        if ($options['type'] === 'yml' && !empty($options['location'])) {
            throw new TerminusException('The --location option is not compatible with --type=yml.');
        }

        $this->log()->notice("Fetching site information to build Drush aliases...");
        $alias_replacements = $this->getSites($options);

        $this->log()->notice("{count} sites found.", ['count' => count($alias_replacements)]);

        // Add with custom domains if requested
        $alias_replacements = $this->addCustomDomains($alias_replacements, $options);

        // Write the alias files (only of the type requested)
        $emitters = $this->getAliasEmitters($options);
        if (empty($emitters)) {
            throw new \Exception('No emitters; nothing to do.');
        }
        foreach ($emitters as $emitter) {
            $this->log()->debug("Emitting aliases via {emitter}", ['emitter' => get_class($emitter)]);
            $this->log()->notice($this->shortenHomePath($emitter->notificationMessage()));
            $emitter->write($alias_replacements);
        }
    }

    /**
     * getAliasEmitters returns a list of emitters based on the provided options.
     *
     * @param array $options Full set of commanline options, some of which may
     *   affect the emitters returned
     * @return AliasEmitterInterface[]
     */
    protected function getAliasEmitters($options)
    {
        $home = $this->getConfig()->get('user_home');
        $base_dir = preg_replace('#^~#', $home, $options['base']);
        $target_name = $options['target'];
        $emitterType = $options['type'];
        if ($options['print']) {
            $emitterType = 'print';
        }
        $location = !empty($options['location']) ? $options['location'] : "$base_dir/$target_name.aliases.drushrc.php";
        $emitters = [];

        if ($this->emitterTypeMatches($emitterType, 'print', false)) {
            $print_nickname = \uniqid(__METHOD__);
            $this->getContainer()->add($print_nickname, PrintingEmitter::class)
                ->addArguments([$this->output()]);
            $emitters[] = $this->getContainer()->get($print_nickname);
        }
        if ($this->emitterTypeMatches($emitterType, 'php')) {
            $php_nickname = \uniqid(__METHOD__);
            $this->getContainer()->add($php_nickname, AliasesDrushRcEmitter::class)
                ->addArguments([$location, $base_dir]);
            $emitters[] = $this->getContainer()->get($php_nickname);
        }
        if ($this->emitterTypeMatches($emitterType, 'yml')) {
            $yml_nickname = \uniqid(__METHOD__);
            $this->getContainer()->add($yml_nickname, DrushSitesYmlEmitter::class)
                ->addArguments([$base_dir, $home, $target_name]);
            $emitters[] = $this->getContainer()->get($yml_nickname);
        }

        return $emitters;
    }

    /**
     * Given a set of site ids, return an id=>name mapping.
     *
     * @param array $site_data Serialized site data
     * @return array Associative array of site name => alias replacement data
     */
    protected function getAliasReplacements($site_data)
    {
        // Convert the array key from site id to site name.
        $site_data = array_combine(
            array_map(function ($siteInfo) {
                return $siteInfo['name'];
            }, $site_data),
            array_values($site_data)
        );

        // Put the data in alphabetical order by site name.
        ksort($site_data);

        return array_map(function ($siteInfo) {
            return [
                'site_name' => $siteInfo['name'],
                'env_name' => '*',
                'env_label' => '${env-name}',
                'site_id' => $siteInfo['id'],
            ];
        }, $site_data);
    }

    /**
     * Look up all available sites, as filtered by --org and --team
     *
     * @param array $options Full set of commanline options, some of which may
     *   affect selected set of sites returned.
     * @return array Associative array of site id => alias replacement data
     */
    protected function getAllSites($options)
    {
        $this->sites()->fetch(
            [
                'org_id' => null,
                'team_only' => false,
            ]
        );
        return $this->getAliasReplacements($this->sites->serialize());
    }

    /**
     * Fetch those sites indicated by the commandline options.
     *
     * @param array $options Full set of commanline options, some of which may
     *   affect selected set of sites returned.
     * @return array
     *   Associative array of site id => site name
     */
    protected function getSites($options)
    {
        if (!empty($options['only'])) {
            return $this->getSpecifiedSites(explode(',', $options['only']));
        }
        if (!$options['all']) {
            return $this->getSitesWithDirectMembership();
        }
        return $this->getAllSites($options);
    }

    /**
     * Look up those sites that the user has a direct membership in
     *
     * @return array Associative array of site id => alias replacement data
     */
    protected function getSitesWithDirectMembership()
    {
        $this->sites()->fetch(
            [
                'org_id' => null,
                'team_only' => true,
            ]
        );
        return $this->getAliasReplacements($this->sites->serialize());
    }

    /**
     * Fetch the sites listed on the command line.
     *
     * @param array $siteList List of site names
     * @return array Associative array of site id => alias replacement data
     */
    protected function getSpecifiedSites($siteList)
    {
        $site_data = [];
        foreach ($siteList as $siteName) {
            $site = $this->sites()->get($siteName);
            $site_data[$site->id] = [
                'id' => $site->id,
                'name' => $siteName,
            ];
        }
        return $this->getAliasReplacements($site_data);
    }

    /**
     * Determine whether the provided emitter type matches the desired emitter
     * type or types
     *
     * @param string $emitterType The type of emitter(s) desired
     * @param string $checkType The type of emitter we are testing for
     * @param bool $default Whether the emitter we are testing for belongs in 'all' or not.
     *
     * @return bool
     */
    protected function emitterTypeMatches($emitterType, $checkType, $default = true)
    {
        if (!$emitterType || ($emitterType === 'all')) {
            return $default;
        }
        return $emitterType === $checkType;
    }

    /**
     * Utility function to convert references to the home path to simply '~'
     *
     * @param string $message
     * @return string
     */
    protected function shortenHomePath($message)
    {
        return str_replace($this->getConfig()->get('user_home') ?? '', '~', $message ?? '');
    }

    /**
     * Enrich alias replacement data with custom domain URIs when requested.
     *
     * @param array $alias_replacements Associative array of site id => alias replacement data
     * @param array $options Command options
     * @return array Modified alias replacement data with custom URIs
     */
    protected function addCustomDomains($alias_replacements, $options)
    {
        // If custom domains feature is not enabled, return unchanged
        if (empty($options['custom-domains'])) {
            return $alias_replacements;
        }

        // Standard environments to generate explicit entries for
        $standard_envs = ['dev', 'test', 'live'];

        foreach ($alias_replacements as $site_name => &$site_data) {
            try {
                $site = $this->sites()->get($site_name);
                $environments = $site->getEnvironments()->all();

                // Build environments array only for dev, test, live
                $site_data['environments'] = [];

                foreach ($environments as $env) {
                    // Only process standard environments
                    if (!in_array($env->id, $standard_envs)) {
                        continue;
                    }

                    $custom_uri = $this->selectBestUri($env);

                    $site_data['environments'][$env->id] = [
                        'site_name' => $site_data['site_name'],
                        'env_name' => $env->id,
                        'env_label' => $env->id,
                        'site_id' => $site_data['site_id'],
                        'custom_uri' => $custom_uri,
                    ];
                }
            } catch (\Exception $e) {
                $this->log()->warning(
                    "Could not fetch domains for site {site}: {error}",
                    [
                        'site' => $site_name,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        return $alias_replacements;
    }

    /**
     * Select the best URI for an environment.
     *
     * Priority:
     * 1. Primary domain (if configured and not a platform domain)
     * 2. First custom domain
     * 3. Platform domain (fallback)
     *
     * @param \Pantheon\Terminus\Models\Environment $environment
     * @return string The selected URI
     */
    protected function selectBestUri($environment)
    {
        try {
            $domains = $environment->getDomains()->all();

            // Check for primary domain first
            foreach ($domains as $domain) {
                if ($domain->get('primary') === true) {
                    $domain_name = $domain->id;
                    if (!$this->isPlatformDomain($domain_name)) {
                        return $domain_name;
                    }
                }
            }

            // Look for first custom domain
            foreach ($domains as $domain) {
                $domain_name = $domain->id;
                $domain_type = $domain->get('type');

                if ($domain_type === 'custom' || !$this->isPlatformDomain($domain_name)) {
                    return $domain_name;
                }
            }
        } catch (\Exception $e) {
            $this->log()->debug(
                "Could not fetch domains for environment {env}: {error}",
                [
                    'env' => $environment->id,
                    'error' => $e->getMessage(),
                ]
            );
        }

        // Fallback to platform domain
        return $this->getPlatformDomain($environment);
    }

    /**
     * Check if a domain is a Pantheon platform domain.
     *
     * @param string $domain
     * @return bool
     */
    protected function isPlatformDomain($domain)
    {
        return (
            strpos($domain, '.pantheonsite.io') !== false ||
            strpos($domain, '.pantheon.io') !== false ||
            strpos($domain, '.gotpantheon.com') !== false
        );
    }

    /**
     * Get the platform domain for an environment.
     *
     * @param \Pantheon\Terminus\Models\Environment $environment
     * @return string
     */
    protected function getPlatformDomain($environment)
    {
        $site = $environment->getSite();
        $env_label = $environment->id;
        $site_name = $site->get('name');

        return "{$env_label}-{$site_name}.pantheonsite.io";
    }
}
