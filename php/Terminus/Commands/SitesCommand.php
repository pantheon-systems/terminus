<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Configurator;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Organization;
use Terminus\Models\Site;
use Terminus\Models\Upstreams;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Session;
use Terminus\Utils;

/**
 * Actions on multiple sites
 *
 * @command sites
 */
class SitesCommand extends TerminusCommand {
  public $sites;

  /**
   * Shows a list of your sites on Pantheon
   *
   * @param array $options Options to construct the command object
   * @return SitesCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * Print and save drush aliases
   *
   * ## OPTIONS
   *
   * [--print]
   * : print aliases to screen
   *
   * [--location=<location>]
   * : Specify the the full path, including the filename, to the alias file
   *   you wish to create. Without this option a default of
   *   '~/.drush/pantheon.aliases.drushrc.php' will be used.
   */
  public function aliases($args, $assoc_args) {
    $user     = Session::getUser();
    $print    = $this->input()->optional(
      array(
        'key'     => 'print',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $location = $this->input()->optional(
      array(
        'key'     => 'location',
        'choices' => $assoc_args,
        'default' => sprintf(
          '%s/.drush/pantheon.aliases.drushrc.php',
          Configurator::getHomeDir()
        ),
      )
    );

    if (is_dir($location)) {
      $message  = 'Please provide a full path with filename,';
      $message .= ' e.g. {location}/pantheon.aliases.drushrc.php';
      $this->failure($message, compact('location'));
    }

    $file_exists = file_exists($location);

    // Create the directory if it doesn't yet exist
    $dirname = dirname($location);
    if (!is_dir($dirname)) {
      mkdir($dirname, 0700, true);
    }

    $content = $user->getAliases();
    $h       = fopen($location, 'w+');
    fwrite($h, $content);
    fclose($h);
    chmod($location, 0700);

    $message = 'Pantheon aliases created';
    if ($file_exists) {
      $message = 'Pantheon aliases updated';
    }
    if (strpos($content, 'array') === false) {
      $message .= ', although you have no sites';
    }
    $this->log()->info($message);

    if ($print) {
      $aliases = str_replace(array('<?php', '?>'), '', $content);
      $this->output()->outputDump($aliases);
    }
  }

  /**
   * Create a new site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Name of the site to create (machine-readable)
   *
   * [--name=<name>]
   * : (deprecated) use --site instead
   *
   * [--label=<label>]
   * : Label for the site
   *
   * [--upstream=<upstreamid>]
   * : Specify the upstream upstream to use
   *
   * [--org=<id>]
   * : UUID of organization into which to add this site
   *
   */
  public function create($args, $assoc_args) {
    $options                = $this->getSiteCreateOptions($assoc_args);
    $options['upstream_id'] = $this->input()->upstream(
      ['args' => $assoc_args,]
    );
    $this->log()->info('Creating new site installation ... ');

    $workflow = $this->sites->addSite($options);
    $workflow->wait();
    $this->workflowOutput($workflow);

    // Add Site to SitesCache
    $final_task = $workflow->get('final_task');
    $this->sites->addSiteToCache($final_task->site_id);

    $this->helpers->launch->launchSelf(
      [
        'command'    => 'site',
        'args'       => ['info',],
        'assoc_args' => ['site' => $options['name'],],
      ]
    );

    return true;
  }

  /**
   * Show all sites user has access to
   * Note: because of the size of this call, it is cached
   *   and also is the basis for loading individual sites by name
   *
   * [--team]
   * : Filter for sites you are a team member of
   *
   * [--owner]
   * : Filter for sites a specific user owns. Use "me" for your own user.
   *
   * [--org=<id>]
   * : Filter sites you can access via the organization. Use 'all' to get all.
   *
   * [--name=<regex>]
   * : Filter sites you can access via name
   *
   * [--cached]
   * : Causes the command to return cached sites list instead of retrieving anew
   *
   * @subcommand list
   * @alias show
   */
  public function index($args, $assoc_args) {
    // Always fetch a fresh list of sites
    if (!isset($assoc_args['cached'])) {
      $this->sites->rebuildCache();
    }
    $sites = $this->sites->all();

    if (isset($assoc_args['team'])) {
      $sites = $this->filterByTeamMembership($sites);
    }
    if (isset($assoc_args['org'])) {
      $org_id = $this->input()->orgId(
        [
          'allow_none' => true,
          'args'       => $assoc_args,
          'default'    => 'all',
        ]
      );
      $sites = $this->filterByOrganizationalMembership($sites, $org_id);
    }

    if (isset($assoc_args['name'])) {
      $sites = $this->filterByName($sites, $assoc_args['name']);
    }

    if (isset($assoc_args['owner'])) {
      $owner_uuid = $assoc_args['owner'];
      if ($owner_uuid == 'me') {
        $owner_uuid = Session::getData()->user_uuid;
      }
      $sites = $this->filterByOwner($sites, $owner_uuid);
    }

    if (count($sites) == 0) {
      $this->log()->warning('You have no sites.');
    }

    $rows = [];
    foreach ($sites as $site) {
      $memberships = [];
      foreach ($site->get('memberships') as $membership) {
        $memberships[$membership['id']] = $membership['name'];
      }
      $rows[$site->get('id')] = [
        'name'          => $site->get('name'),
        'id'            => $site->get('id'),
        'service_level' => $site->get('service_level'),
        'framework'     => $site->get('framework'),
        'owner'         => $site->get('owner'),
        'created'       => date(TERMINUS_DATE_FORMAT, $site->get('created')),
        'memberships'   => $memberships,
      ];
      if ((boolean)$site->get('frozen')) {
        $rows[$site->get('id')]['frozen'] = true;
      }
    }

    usort(
      $rows,
      function($row_1, $row_2) {
        $comparison = strcasecmp($row_1['name'], $row_2['name']);
        return $comparison;
      }
    );

    $labels = [
      'name'          => 'Name',
      'id'            => 'ID',
      'service_level' => 'Service Level',
      'framework'     => 'Framework',
      'owner'         => 'Owner',
      'created'       => 'Created',
      'memberships'   => 'Memberships',
    ];
    $this->output()->outputRecordList($rows, $labels);
  }

  /**
   * Update all dev sites with an available upstream update.
   *
   * ## OPTIONS
   *
   * [--report]
   * : If set output will contain list of sites and whether they are up-to-date
   *
   * [--upstream=<upstream>]
   * : Specify a specific upstream to check for updating.
   *
   * [--no-updatedb]
   * : Use flag to skip running update.php after the update has applied
   *
   * [--xoption=<theirs|ours>]
   * : Corresponds to git's -X option, set to 'theirs' by default
   *   -- https://www.kernel.org/pub/software/scm/git/docs/git-merge.html
   *
   * [--tag=<tag>]
   * : Tag to filter by
   *
   * [--org=<id>]
   * : Only necessary if using --tag. Organization which has tagged the site
   *
   * [--cached]
   * : Set to prevent rebuilding of sites cache
   *
   * @subcommand mass-update
   */
  public function massUpdate($args, $assoc_args) {
    // Ensure the sitesCache is up to date
    if (!isset($assoc_args['cached'])) {
      $this->sites->rebuildCache();
    }

    $upstream = $this->input()->optional(
      array(
        'key'     => 'upstream',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $data     = array();
    $report   = $this->input()->optional(
      array(
        'key'     => 'report',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $confirm   = $this->input()->optional(
      array(
        'key'     => 'confirm',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $tag       = $this->input()->optional(
      array(
        'key'     => 'tag',
        'choices' => $assoc_args,
        'default' => false,
      )
    );

    $org = '';
    if ($tag) {
      $org = $this->input()->orgId(array('args' => $assoc_args));
    }
    $sites = $this->sites->filterAllByTag($tag, $org);

    // Start status messages.
    if ($upstream) {
      $this->log()->info(
        'Looking for sites using {upstream}.',
        compact('upstream')
      );
    }

    foreach ($sites as $site) {
      $context = array('site' => $site->get('name'));
      $site->fetch();
      $updates = $site->getUpstreamUpdates();
      if (!isset($updates->behind)) {
        // No updates, go back to start.
        continue;
      }
      // Check for upstream argument and site upstream URL match.
      $siteUpstream = $site->info('upstream');
      if ($upstream && isset($siteUpstream->url)) {
        if ($siteUpstream->url <> $upstream) {
          // Uptream doesn't match, go back to start.
          continue;
        }
      }

      if ($updates->behind > 0) {
        $data[$site->get('name')] = array(
          'site'   => $site->get('name'),
          'status' => 'Needs update'
        );
        $env = $site->environments->get('dev');
        if ($env->info('connection_mode') == 'sftp') {
          $message  = '{site} has available updates, but is in SFTP mode.';
          $message .= ' Switch to Git mode to apply updates.';
          $this->log()->warning($message, $context);
          $data[$site->get('name')] = array(
            'site'=> $site->get('name'),
            'status' => 'Needs update - switch to Git mode'
          );
          continue;
        }
        $updatedb = !$this->input()->optional(
          array(
            'key'     => 'updatedb',
            'choices' => $assoc_args,
            'default' => false,
          )
        );
        $xoption  = !$this->input()->optional(
          array(
            'key'     => 'xoption',
            'choices' => $assoc_args,
            'default' => 'theirs',
          )
        );
        if (!$report) {
          $message = 'Apply upstream updates to %s ';
          $message .= '( run update.php:%s, xoption:%s ) ';
          $confirmed = $this->input()->confirm(
            array(
              'message' => $message,
              'context' => array(
                $site->get('name'),
                var_export($updatedb, 1),
                var_export($xoption, 1)
              ),
              'exit' => false,
            )
          );
          if (!$confirmed) {
            continue; // User says No, go back to start.
          }
          // Back up the site so it may be restored should something go awry
          $this->log()->info('Backing up {site}.', $context);
          $backup = $env->backups->create(['element' => 'all',]);
          // Only continue if the backup was successful.
          if ($backup) {
            $this->log()->info('Backup of {site} created.', $context);
            $this->log()->info('Updating {site}.', $context);
            $response = $site->applyUpstreamUpdates(
              $env->get('id'),
              $updatedb,
              $xoption
            );
            $data[$site->get('name')]['status'] = 'Updated';
            $this->log()->info('{site} is updated.', $context);
          } else {
            $data[$site->get('name')]['status'] = 'Backup failed';
            $this->failure(
              'There was a problem backing up {site}. Update aborted.',
              $context
            );
          }
        }
      } else {
        if (isset($assoc_args['report'])) {
          $data[$site->get('name')] = array(
            'site'   => $site->get('name'),
            'status' => 'Up to date'
          );
        }
      }
    }

    if (!empty($data)) {
      sort($data);
      $this->output()->outputRecordList($data);
    } else {
      $this->log()->info('No sites in need of updating.');
    }
  }

  /**
   * Filters an array of sites by whether the user is an organizational member
   *
   * @param Site[] $sites An array of sites to filter by
   * @param string $regex Non-delimited PHP regex to filter site names by
   * @return Site[]
   */
  private function filterByName($sites, $regex = '(.*)') {
    $filtered_sites = array_filter(
      $sites,
      function($site) use ($regex) {
        preg_match("~$regex~", $site->get('name'), $matches);
        $is_match = !empty($matches);
        return $is_match;
      }
    );
    return $filtered_sites;
  }

  /**
   * Filters an array of sites by whether the user is an organizational member
   *
   * @param Site[] $sites      An array of sites to filter by
   * @param string $owner_uuid UUID of the owning user to filter by
   * @return Site[]
   */
  private function filterByOwner($sites, $owner_uuid) {
    $filtered_sites = array_filter(
      $sites,
      function($site) use ($owner_uuid) {
        $is_owner = ($site->get('owner') == $owner_uuid);
        return $is_owner;
      }
    );
    return $filtered_sites;
  }

  /**
   * Filters an array of sites by whether the user is an organizational member
   *
   * @param Site[] $sites  An array of sites to filter by
   * @param string $org_id ID of the organization to filter for
   * @return Site[]
   */
  private function filterByOrganizationalMembership($sites, $org_id = 'all') {
    $filtered_sites = array_filter(
      $sites,
      function($site) use ($org_id) {
        $memberships    = $site->get('memberships');
        foreach ($memberships as $membership) {
          if ((($org_id == 'all') && ($membership['type'] == 'organization'))
            || ($membership['id'] === $org_id)
          ) {
            return true;
          }
        }
        return false;
      }
    );
    return $filtered_sites;
  }

  /**
   * Filters an array of sites by whether the user is a team member
   *
   * @param Site[] $sites An array of sites to filter by
   * @return Site[]
   */
  private function filterByTeamMembership($sites) {
    $filtered_sites = array_filter(
      $sites,
      function($site) {
        $memberships    = $site->get('memberships');
        foreach ($memberships as $membership) {
          if ($membership['name'] == 'Team') {
            return true;
          }
        }
        return false;
      }
    );
    return $filtered_sites;
  }

  /**
   * A helper function for getting/prompting for the site create options.
   *
   * @param array $assoc_args Arguments from command
   * @return array
   */
  private function getSiteCreateOptions($assoc_args) {
    $options          = [];
    $options['label'] = $this->input()->string(
      [
        'args'     => $assoc_args,
        'key'      => 'label',
        'message'  => 'Human-readable label for the site',
        'required' => true,
      ]
    );
    $suggested_name   = $this->sanitizeName($options['label']);

    if (array_key_exists('name', $assoc_args)) {
      // Deprecated but kept for backwards compatibility
      $options['site_name'] = $assoc_args['name'];
    } elseif (array_key_exists('site', $assoc_args)) {
      $options['site_name'] = $assoc_args['site'];
    } elseif (isset($_SERVER['TERMINUS_SITE'])) {
      $options['site_name'] = $_SERVER['TERMINUS_SITE'];
    } else {
      $message  = 'Machine name of the site; used as part of the default URL';
      $message .= " (if left blank will be $suggested_name)";

      $options['site_name'] = $this->input()->string(
        [
          'args'     => $assoc_args,
          'key'      => 'site',
          'default'  => $suggested_name,
          'message'  => $message,
          'required' => true,
        ]
      );
    }
    if (isset($assoc_args['org'])) {
      $options['organization_id'] = $this->input()->orgId(
        ['args' => $assoc_args, 'default' => false,]
      );
    }
    return $options;
  }

  /**
   * Sanitize the site name field
   *
   * @param string $string String to be sanitized
   * @return string Param string, sanitized
   */
  private function sanitizeName($string) {
    $name = $string;
    // squash whitespace
    $name = trim(preg_replace('#\s+#', ' ', $name));
    // replace spacers with hyphens
    $name = preg_replace("#[\._ ]#", "-", $name);
    // crush everything else
    $name = strtolower(preg_replace("#[^A-Za-z0-9-]#", "", $name));
    return $name;
  }

}
