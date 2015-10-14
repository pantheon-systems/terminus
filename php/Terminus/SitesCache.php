<?php

namespace Terminus;

use Terminus;
use TerminusCommand;
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
    $cache = (array)$this->cache->get_data(
      $this->cachekey,
      array('decode_array' => true)
    );

    //If a single site item is passed in, wrap it in an array
    if (isset($memberships_data['id'])) {
      $memberships_data = array($memberships_data);
    }

    foreach ($memberships_data as $membership_data) {
      $site_name = $membership_data['name'];
      $membership = $membership_data['membership'];
      $membership_id = $membership_data['membership']['id'];

      //If site is not in the cache, add it as a new entry
      if (!isset($cache[$site_name])) {
        $cache[$site_name] = $this->getSiteData($membership_data);
      }

      //Then add the membership
      $cache[$site_name] = array_merge(
        $cache[$site_name],
        array('memberships' => array($membership_id => $membership))
      );
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
    $response = TerminusCommand::pagedRequest('users/' . $user_id . '/memberships/sites');

    $memberships_data = array();
    foreach ($response['data'] as $membership) {
      $site = (array)$membership->site;
      $member_data = array('id' => $user_id, 'name' => 'Team', 'type' => 'team');
      $memberships_data[] = $this->getSiteData($site, $member_data);
    }

    return $memberships_data;
  }

  public function fetch_user_organizations() {
    $response = TerminusCommand::pagedRequest('users/' . Session::getValue('user_uuid') . '/memberships/organizations');

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
    $response = TerminusCommand::pagedRequest('organizations/' . $org_data['id'] . '/memberships/sites');

    $memberships_data = array();
    foreach ($response['data'] as $membership) {
      $site_data = (array)$membership->site;
      $org_data['type'] = 'organization';
      $memberships_data[] = $this->getSiteData($site_data, $org_data);
    }

    return $memberships_data;
  }

  /**
   * Formats site data from response for use
   *
   * @param [array] $response_data    Data about the site from API
   * @param [array] $memebership_data Data about membership to this site
   * @return [array] $membership_array
   */
  private function getSiteData($response_data, $membership_data = array()) {
    $site_data = array(
      'id'            => null,
      'name'          => null,
      'label'         => null,
      'created'       => null,
      'framework'     => null,
      'organization'  => null,
      'service_level' => null,
      'upstream'      => null,
      'php_version'   => null,
      'holder_type'   => null,
      'holder_id'     => null,
      'owner'         => null,
      'membership'    => array(),
    );
    foreach ($site_data as $index => $value) {
      if (($value == null) && isset($response_data[$index])) {
        $site_data[$index] = $response_data[$index];
      }
    }

    if (!empty($membership_data)) {
      $site_data['membership'] = $membership_data;
    }
    return $site_data;
  }

}
