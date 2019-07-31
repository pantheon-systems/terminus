<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;

use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

use Pantheon\Terminus\Helpers\AliasEmitters\AliasCollection;
use Pantheon\Terminus\Helpers\AliasEmitters\AliasData;
use Pantheon\Terminus\Helpers\AliasEmitters\AliasesDrushRcEmitter;
use Pantheon\Terminus\Helpers\AliasEmitters\PrintingEmitter;
use Pantheon\Terminus\Helpers\AliasEmitters\DrushSitesYmlEmitter;

/**
 * Generate lots of aliases
 */
class AliasesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Generates Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     *
     * @authenticated
     *
     * @command aliases
     * @aliases alpha:aliases
     *
     * @option boolean $print Print aliases only (Drush 8 format)
     * @option string $location Path and filename for php aliases.
     * @option boolean $all Include all sites available, including team memberships.
     * @option string $only Only generate aliases for sites in the specified comma-separated list. This option is only recommended for use in CI scripts.
     * @option string $type Type of aliases to create: 'php', 'yml' or 'all'.
     * @option string $base Base directory to write .yml aliases.
     * @option string $target Base name to use to generate path to alias files.
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
    ])
    {
        // Be forgiving about the spelling of 'yaml'
        if ($options['type'] == 'yaml') {
            $options['type'] = 'yml';
        }

        $this->log()->notice("Fetching site information to build Drush aliases...");
        $site_ids = $this->getSites($options);

        // Collect information on the requested sites
        $collection = $this->getAliasCollection($site_ids);

        $this->log()->notice("{count} sites found.", ['count' => count($site_ids)]);

        // Write the alias files (only of the type requested)
        $emitters = $this->getAliasEmitters($options);
        if (empty($emitters)) {
            throw new \Exception('No emitters; nothing to do.');
        }
        foreach ($emitters as $emitter) {
            $this->log()->debug("Emitting aliases via {emitter}", ['emitter' => get_class($emitter)]);
            $this->log()->notice($this->shortenHomePath($emitter->notificationMessage()));
            $emitter->write($collection);
        }
    }

    protected function shortenHomePath($message)
    {
        return str_replace($this->getConfig()->get('user_home'), '~', $message);
    }

    /**
     * Fetch those sites indicated by the commandline options.
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
     * Fetch the sites listed on the command line.
     */
    protected function getSpecifiedSites($siteList)
    {
        $result = [];
        foreach ($siteList as $siteName) {
            $site = $this->sites()->get($siteName);
            $result[$site->id] = $siteName;
        }
        return $result;
    }

    /**
     * Look up all available sites, as filtered by --org and --team
     */
    protected function getAllSites($options)
    {
        $this->sites()->fetch(
            [
                'org_id' => null,
                'team_only' => false,
            ]
        );
        return $this->getSiteNames($this->sites->ids());
    }

    /**
     * Look up those sites that the user has a direct membership in
     */
    protected function getSitesWithDirectMembership()
    {
        $this->sites()->fetch(
            [
                'org_id' => null,
                'team_only' => true,
            ]
        );
        return $this->getSiteNames($this->sites->ids());
    }

    /**
     * Given a set of site ids, return an id=>name mapping.
     */
    protected function getSiteNames($site_ids)
    {
        $result = [];
        foreach ($site_ids as $site_id) {
            $site = $this->sites->get($site_id);
            $site_name = $site->get('name');
            $result[$site_id] = $site_name;
        }
        return $result;
    }

    /**
     * getAliasEmitters returns a list of emitters based on the provided options.
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
            $emitters[] = new PrintingEmitter($this->output());
        }
        if ($this->emitterTypeMatches($emitterType, 'php')) {
            $emitters[] = new AliasesDrushRcEmitter($location, $base_dir);
        }
        if ($this->emitterTypeMatches($emitterType, 'yml')) {
            $emitters[] = new DrushSitesYmlEmitter($base_dir, $home, $target_name);
        }

        return $emitters;
    }

    protected function emitterTypeMatches($emitterType, $checkType, $default = true)
    {
        if (!$emitterType || ($emitterType === 'all')) {
            return $default;
        }
        return $emitterType === $checkType;
    }

    protected function getAliasCollection($site_ids)
    {
        $collection = new AliasCollection();

        foreach ($site_ids as $site_id => $site_name) {
            $alias = new AliasData($site_name, '*', $site_id);
            $collection->add($alias);
        }

        return $collection;
    }
}
