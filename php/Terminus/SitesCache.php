<?php
namespace Terminus;

use Symfony\Component\Finder\Finder;
use Terminus;
use Terminus\FileCache;
use \TerminusCommand;
use Terminus\Session;

/**
 * Persists a mapping between Site names and Site IDs
 * This is stored in the filecache in a json schema like so:
 *
 *     {
 *       "<site_name>" : {
 *         id: "<site_uuid>",
 *         name: "<site_name>",
 *         created: "<timestamp>",
 *         framework: "<framework>",
 *         service_level: "<service_level>"
 *         memberships: {
 *           "<membership_id>": {
 *             id: "<membership_id",
 *             name: "<Team|Organization Name>"
 *             type: "<team|organization>",
 *           },...
 *         }
 *       },...
 *     }
 *
 */
class SitesCache {
  protected $cache;
  protected $cachekey = 'sites';

  public function __construct() {
    $this->cache = Terminus::get_cache();
  }

  private function find($name, $options = array()) {
    $defaults = array('rebuild' => true);
    $options = array_merge($defaults, $options);
    $all = $this->all();

    if (isset($all[$name])) {
      $site_data = $all[$name];
      return $site_data;
    } else {
      if ($options['rebuild']) {
        $this->rebuild();
        $recurse_without_rebuild = $this->find($name, array('rebuild' => false));
        return $recurse_without_rebuild;
      } else {
        return null;
      }
    }
  }

  /**
   * Searches the SitesCache for an ID given a name
   *
   */
  public function findID($name, $options = array()) {
    $site_data = $this->find($name, $options);
    if ($site_data) {
      $site_id = $site_data['id'];
      return $site_id;
    } else {
      return null;
    }
  }

  public function all() {
    $cache_data = $this->cache->get_data($this->cachekey, array('decode_array' => true));
    if ($cache_data) {
      return $cache_data;
    } else {
      return array();
    }
  }

  /**
   * Adds a record for a site to the cache. Records should either be a single assoc_aray
   * or a list of arrays that look like this:
   *
   *     array(
   *       "id" => "<site_id>"
   *       "name" => "<site_name>"
   *       "created" => "<timestamp>",
   *       "framework" => "<framework>",
   *       "service_level" => "<service_level>"
   *       "membership" => array(
   *         'id' => "<user_id|org_id>"
   *         'name' => "<Team|org_name>"
   *         'type' => "<team|organization>"
   *       )
   *     )
   */
  public function add($memberships_data = array()) {
    $cache = (array) $this->cache->get_data($this->cachekey, array('decode_array' => true));

    // if a single site item is passed in, wrap it in an array
    if (isset($memberships_data['id'])) {
      $memberships_data = array($memberships_data);
    }

    foreach ($memberships_data as $membership_data) {
      $site_id = $membership_data['id'];
      $site_name = $membership_data['name'];
      $site_created = $membership_data['created'];
      $site_framework = $membership_data['framework'];
      $site_service_level = $membership_data['service_level'];
      $membership = $membership_data['membership'];
      $membership_id = $membership_data['membership']['id'];

      if (!isset($cache[$site_name]) || !isset($cache[$site_name]['memberships'])) {
        // if site is not in cache, add a new entry
        $cache[$site_name] = array(
          'id' => $site_id,
          'name' => $site_name,
          'created' => $site_created,
          'framework' => $site_framework,
          'service_level' => $site_service_level,
          'memberships' => array()
        );
      }

      // then add the membership
      $cache[$site_name]['memberships'][$membership_id] = $membership;
    }

    # and save the cache
    $this->cache->put_data($this->cachekey, $cache);

    return $this->all();
  }

  public function rebuild() {
    // Clear out the cache
    $this->cache->put_data($this->cachekey, array());

    // Add user's own sites
    $this->add($this->fetch_user_sites());

    // Add all sites for each of user's organizations
    $orgs_data = $this->fetch_user_organizations();

    foreach ($orgs_data as $org_data) {
      $this->add($this->fetch_organization_sites($org_data));
    }

    return $this->all();
  }

  public function remove($sitename) {
    $cache = (array) $this->cache->get_data($this->cachekey, array('decode_array' => true));
    unset($cache[$sitename]);
    $this->cache->put_data($this->cachekey, $cache);
  }

  public function clear() {
    $this->cache->put_data($this->cachekey, array());
  }

  public function fetch_user_sites() {
    $user_id = Session::getValue('user_uuid');
    $response = TerminusCommand::paged_request('users/' . $user_id . '/memberships/sites');

    $memberships_data = array();
    foreach ($response['data'] as $membership) {
      $site = $membership->site;

      $memberships_data[] = array(
        'id' => $site->id,
        'name' => $site->name,
        'created' => property_exists($site, 'created') ? $site->created : null,
        'framework' => property_exists($site, 'framework') ? $site->framework : null,
        'service_level' => property_exists($site, 'service_level') ? $site->service_level : null,
        'membership' => array(
          'id' => $user_id,
          'name' => 'Team',
          'type' => 'team',
        )
      );
    }

    return $memberships_data;
  }

  public function fetch_user_organizations() {
    $response = TerminusCommand::paged_request('users/' . Session::getValue('user_uuid') . '/memberships/organizations');

    $data = array();
    foreach ($response['data'] as $membership) {
      if ($membership->role == 'unprivileged') {
        // Users with unprivileged role in organizations can't see organization sites
        // but must be added to the team
        continue;
      }

      $data[] = array(
        'id' => $membership->id,
        'name' => $membership->organization->profile->name,
        'type' => 'organization'
      );
    }

    return $data;
  }

  public function fetch_organization_sites($org_data) {
    $response = TerminusCommand::paged_request('organizations/' . $org_data['id'] . '/memberships/sites');

    $memberships_data = array();
    foreach ($response['data'] as $membership) {
      $memberships_data[] = array(
        'id' => $membership->site->id,
        'name' => $membership->site->name,
        'created' => $membership->site->created,
        'framework' => $membership->site->framework,
        'service_level' => $membership->site->service_level,
        'membership' => array(
          'id' => $org_data['id'],
          'name' => $org_data['name'],
          'type' => 'organization',
        )
      );
    }

    return $memberships_data;
  }
}
