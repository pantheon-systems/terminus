<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Auth;
use Terminus\Session;
use Terminus\Utils;
use Terminus\Commands\TerminusCommand;
use Terminus\Helpers\Input;
use Terminus\Models\Organization;
use Terminus\Models\Site;
use Terminus\Models\Upstreams;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Models\Collections\Sites;

/**
 * Actions on multiple sites
 */
class SitesCommand extends TerminusCommand {
  public $sites;

  /**
   * Shows a list of your sites on Pantheon
   */
  public function __construct() {
    Auth::ensureLogin();
    parent::__construct();
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
    $print    = Input::optional(
      array(
        'key'     => 'print',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $location = Input::optional(
      array(
        'key'     => 'location',
        'choices' => $assoc_args,
        'default' => getenv('HOME') . '/.drush/pantheon.aliases.drushrc.php',
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
    $options  = $this->getSiteCreateOptions($assoc_args);
    $upstream = Input::upstream(array('args' => $assoc_args));
    $options['upstream_id'] = $upstream->get('id');
    $this->log()->info(
      'Creating new {upstream} installation ... ',
      array('upstream' => $upstream->get('longname'))
    );

    $workflow = $this->sites->addSite($options);
    $workflow->wait();
    $this->workflowOutput($workflow);

    // Add Site to SitesCache
    $final_task = $workflow->get('final_task');
    $this->sites->addSiteToCache($final_task->site_id);

    Terminus::launchSelf(
      'site', array('info'), array(
      'site' => $options['name'],
      )
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
    $options = SitesCommand::getSiteCreateOptions($assoc_args);

    $url = Input::string(
      array(
        'args'    => $assoc_args,
        'key'     => 'url',
        'message' => 'URL of archive to import',
      )
    );
    if (!$url) {
      $this->logger->error('Please enter a URL.');
    }

    try {
      //If the site does not yet exist, it will throw an error.
      $site = $this->sites->get($options['name']);
      $this->logger->error(
        sprintf('A site named %s already exists.', $options['name'])
      );
      exit;
    } catch (\Exception $e) {
      //Creating a new site
      $workflow = $this->sites->addSite($options);
      $workflow->wait();
      $this->workflowOutput($workflow);

      //Add site to SitesCache
      $final_task = $workflow->get('final_task');
      $site       = $this->sites->addSiteToCache($final_task->site_id);
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
   * : Filter sites you are a team member of
   *
   * [--org=<id>]
   * : Filter sites you can access via the organization
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

    $rows = array();
    foreach ($sites as $site) {
      $memberships = array();
      foreach ($site->get('memberships') as $membership) {
        $memberships[$membership['id']] = $membership['name'];
      }
      $rows[$site->get('id')] = array(
        'name' => $site->get('name'),
        'id' => $site->get('id'),
        'service_level' => $site->get('service_level'),
        'framework' => $site->get('framework'),
        'created' => date('Y-m-d H:i:s', $site->get('created')),
        'memberships' => $memberships,
      );
    }

    usort(
      $rows,
      function($row_1, $row_2) {
        $comparison = strcasecmp($row_1['name'], $row_2['name']);
        return $comparison;
      }
    );

    if (isset($assoc_args['team'])) {
      $rows = array_filter(
        $rows,
        function($site) {
          $is_membership = in_array('Team', $site['memberships']);
          return $is_membership;
        }
      );
    }

    if (isset($assoc_args['org'])) {
      $org_id = $assoc_args['org'];

      $rows = array_filter(
        $rows,
        function($site) use ($org_id) {
          $is_member = (
            isset($org_ids[$org_id])
            || in_array($org_id, $site['memberships'])
          );
          return $is_member;
        }
      );
    }

    if (isset($assoc_args['name'])) {
      $search_string = $assoc_args['name'];

      $rows = array_filter(
        $rows,
        function($site) use ($search_string) {
          preg_match("~$search_string~", $site['name'], $matches);
          $is_match = !empty($matches);
          return $is_match;
        }
      );
    }

    if (count($rows) == 0) {
      $this->log()->warning('You have no sites.');
    }

    $labels = array(
      'name'          => 'Name',
      'id'            => 'ID',
      'service_level' => 'Service Level',
      'framework'     => 'Framework',
      'created'       => 'Created',
      'memberships'   => 'Memberships'
    );
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

    $upstream = Input::optional(
      array(
        'key'     => 'upstream',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $data     = array();
    $report   = Input::optional(
      array(
        'key'     => 'report',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $confirm   = Input::optional(
      array(
        'key'     => 'confirm',
        'choices' => $assoc_args,
        'default' => false,
      )
    );
    $tag       = Input::optional(
      array(
        'key'     => 'tag',
        'choices' => $assoc_args,
        'default' => false,
      )
    );

    $org = '';
    if ($tag) {
      $org = Input::orgId(array('args' => $assoc_args));
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
        if ($env->getConnectionMode() == 'sftp') {
          $message  = '{site} has available updates, but is in SFTP mode.';
          $message .= ' Switch to Git mode to apply updates.';
          $this->log()->warning($message, $context);
          $data[$site->get('name')] = array(
            'site'=> $site->get('name'),
            'status' => 'Needs update - switch to Git mode'
          );
          continue;
        }
        $updatedb = !Input::optional(
          array(
            'key'     => 'updatedb',
            'choices' => $assoc_args,
            'default' => false,
          )
        );
        $xoption  = !Input::optional(
          array(
            'key'     => 'xoption',
            'choices' => $assoc_args,
            'default' => 'theirs',
          )
        );
        if (!$report) {
          $message = 'Apply upstream updates to %s ';
          $message .= '( run update.php:%s, xoption:%s ) ';
          $confirmed = Input::confirm(
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
          // Backup the DB so the client can restore if something goes wrong.
          $this->log()->info('Backing up {site}.', $context);
          $backup = $env->createBackup(array('element'=>'all'));
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
   * A helper function for getting/prompting for the site create options.
   *
   * @param array $assoc_args Arguments from command
   * @return array
   */
  private function getSiteCreateOptions($assoc_args) {
    $options          = array();
    $options['label'] = Input::string(
      array(
        'args'    => $assoc_args,
        'key'     => 'label',
        'message' => 'Human-readable label for the site',
      )
    );
    $suggested_name   = Utils\sanitizeName($options['label']);

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

      $options['name'] = Input::string(
        array(
          'args'    => $assoc_args,
          'key'     => 'site',
          'message' => $message,
          'deafult' => $suggested_name,
        )
      );
    }
    if (isset($assoc_args['org'])) {
      $options['organization_id'] = Input::orgId(
        array('args' => $assoc_args, 'default' => false)
      );
    }
    return $options;
  }

}

Terminus::addCommand('sites', 'SitesCommand');
