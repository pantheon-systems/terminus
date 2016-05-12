<?php

namespace Terminus\Commands;

use Terminus\Configurator;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Site;
use Terminus\Models\Upstreams;
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
    $this->user  = Session::getUser();
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
    $print    = $this->input()->optional(
      [
        'key'     => 'print',
        'choices' => $assoc_args,
        'default' => false,
      ]
    );
    $location = $this->input()->optional(
      [
        'key'     => 'location',
        'choices' => $assoc_args,
        'default' => sprintf(
          '%s/.drush/pantheon.aliases.drushrc.php',
          Configurator::getHomeDir()
        ),
      ]
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

    $content = $this->user->getAliases();
    file_put_contents($location, $content);
    chmod($location, 0700);

    $message = 'Pantheon aliases created';
    if ($file_exists) {
      $message = 'Pantheon aliases updated';
    }
    $this->log()->info($message);

    if ($print) {
      $aliases = str_replace(['<?php', '?>',], '', $content);
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

    if ($this->sites->nameIsTaken($options['site_name'])) {
      $this->failure(
        'The name {site_name} is taken. Please select a different name.',
        ['site_name' => $data['site_name'],]
      );
    }

    $workflow = $this->sites->create($options);
    $workflow->wait();
    $this->workflowOutput($workflow);

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
   * Import a new site
   *
   * ## OPTIONS
   *
   * [--url=<url>]
   * : URL of archive to import
   *
   * [--name=<name>]
   * : (deprecated) use --site instead
   *
   * [--site=<site>]
   * : Name of the site to create (machine-readable)
   *
   * [--label=<label>]
   * : Label for the site
   *
   * [--org=<id>]
   * : UUID of organization into which to add this site
   *
   * @subcommand import
   */
  public function import($args, $assoc_args) {
    $options = $this->getSiteCreateOptions($assoc_args);

    $url = $this->input()->string(
      [
        'args'    => $assoc_args,
        'key'     => 'url',
        'message' => 'URL of archive to import',
      ]
    );
    if (!$url) {
      $this->log()->error('Please enter a URL.');
    }

    try {
      //If the site does not yet exist, it will throw an error.
      $site = $this->sites->get($options['name']);
      $this->log()->error(
        sprintf('A site named %s already exists.', $options['name'])
      );
      exit;
    } catch (\Exception $e) {
      //Creating a new site
      $workflow = $this->sites->create($options);
      $workflow->wait();
      $this->workflowOutput($workflow);

      $final_task = $workflow->get('final_task');
      $site       = $this->sites->get($final_task->site_id);
      sleep(10); //Avoid false site-DNE errors
    }

    $workflow = $site->import($url);
    $workflow->wait();
    $this->workflowOutput($workflow);
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
   * @subcommand list
   * @alias show
   */
  public function index($args, $assoc_args) {
    $options = [
      'org_id'    => $this->input()->optional(
        [
          'choices' => $assoc_args,
          'default' => null,
          'key'     => 'org',
        ]
      ),
      'team_only' => isset($assoc_args['team']),
    ];
    $this->sites->fetch($options);

    if (isset($assoc_args['name'])) {
      $this->sites->filterByName($assoc_args['name']);
    }
    if (isset($assoc_args['owner'])) {
      $owner_uuid = $assoc_args['owner'];
      if ($owner_uuid == 'me') {
        $owner_uuid = $this->user->id;
      }
      $this->sites->filterByOwner($owner_uuid);
    }
    $sites = $this->sites->all();

    if (count($sites) == 0) {
      $this->log()->warning('You have no sites.');
    }

    $rows = [];
    foreach ($sites as $site) {
      $memberships = [];
      foreach ($site->memberships as $membership) {
        if (property_exists($membership, 'user')) {
          $memberships[] = "{$membership->user->id}: Team";
        } elseif (property_exists($membership, 'organization')) {
          $profile       = $membership->organization->get('profile');
          $memberships[] = "{$membership->organization->id}: {$profile->name}";
        }
      }
      $rows[$site->id] = [
        'name'          => $site->get('name'),
        'id'            => $site->id,
        'service_level' => $site->get('service_level'),
        'framework'     => $site->get('framework'),
        'owner'         => $site->get('owner'),
        'created'       => date(TERMINUS_DATE_FORMAT, $site->get('created')),
        'memberships'   => implode(', ', $memberships),
      ];
      if (!is_null($site->get('frozen'))) {
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
    $this->sites->fetch();
    $upstream = $this->input()->optional(
      [
        'key'     => 'upstream',
        'choices' => $assoc_args,
        'default' => false,
      ]
    );
    $data     = [];
    $report   = $this->input()->optional(
      [
        'key'     => 'report',
        'choices' => $assoc_args,
        'default' => false,
      ]
    );

    if (isset($assoc_args['tag'])) {
      $org = $this->input()->orgId(['args' => $assoc_args,]);
      $this->sites->filterByTag($assoc_args['tag'], $org);
    }
    $sites = $this->sites->all();

    // Start status messages.
    if ($upstream) {
      $this->log()->info(
        'Looking for sites using {upstream}.',
        compact('upstream')
      );
    }

    foreach ($sites as $site) {
      $context = ['site' => $site->get('name'),];
      $updates = $site->getUpstreamUpdates();
      if (!isset($updates->behind)) {
        // No updates, go back to start.
        continue;
      }
      // Check for upstream argument and site upstream URL match.
      $site_upstream = $site->info('upstream');
      if ($upstream && isset($site_upstream->url)) {
        if ($site_upstream->url <> $upstream) {
          // Uptream doesn't match, go back to start.
          continue;
        }
      }

      if ($updates->behind > 0) {
        $data[$site->get('name')] = [
          'site'   => $site->get('name'),
          'status' => 'Needs update',
        ];
        $env = $site->environments->get('dev');
        if ($env->info('connection_mode') == 'sftp') {
          $message  = '{site} has available updates, but is in SFTP mode.';
          $message .= ' Switch to Git mode to apply updates.';
          $this->log()->warning($message, $context);
          $data[$site->get('name')] = [
            'site'=> $site->get('name'),
            'status' => 'Needs update - switch to Git mode',
          ];
          continue;
        }
        $updatedb = !$this->input()->optional(
          [
            'key'     => 'updatedb',
            'choices' => $assoc_args,
            'default' => false,
          ]
        );
        $xoption  = !$this->input()->optional(
          [
            'key'     => 'xoption',
            'choices' => $assoc_args,
            'default' => 'theirs',
          ]
        );
        if (!$report) {
          $message = 'Apply upstream updates to %s ';
          $message .= '( run update.php:%s, xoption:%s ) ';
          $confirmed = $this->input()->confirm(
            [
              'message' => $message,
              'context' => [
                $site->get('name'),
                var_export($updatedb, 1),
                var_export($xoption, 1)
              ],
              'exit' => false,
            ]
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
          $data[$site->get('name')] = [
            'site'   => $site->get('name'),
            'status' => 'Up to date'
          ];
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
      $options['name'] = $assoc_args['name'];
    } elseif (array_key_exists('site', $assoc_args)) {
      $options['name'] = $assoc_args['site'];
    } elseif (isset($_SERVER['TERMINUS_SITE'])) {
      $options['name'] = $_SERVER['TERMINUS_SITE'];
    } else {
      $message  = 'Machine name of the site; used as part of the default URL';
      $message .= " (if left blank will be $suggested_name)";

      $options['name'] = $this->input()->string(
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
