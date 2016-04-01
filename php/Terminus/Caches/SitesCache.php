<?php

namespace Terminus\Caches;

use Terminus\Caches\FileCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Request;
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
  /**
   * @var FileCache
   */
  protected $cache;
  /**
   * @var string
   */
  protected $cachekey;
  /**
   * @var Request
   */
  protected $request;

  /**
   * Object constructor, saves cache to cache property
   */
  public function __construct() {
    $this->cache    = new FileCache();
    $this->cachekey = Session::instance()->get('user_uuid', '') . '_sites';
    $this->request  = new Request();
  }

  /**
   * Adds a record for a site to the cache. Records should either be a single
   * assoc_array or a list of arrays that look like this:
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
   *
   * @param array $memberships_data Memberships of use to add to cache
   * @return array
   */
  public function add(array $memberships_data = []) {
    $cache = (array)$this->cache->getData(
      $this->cachekey,
      ['decode_array' => true,]
    );
    if (!$cache) {
      $cache = [];
    }

    //If a single site item is passed in, wrap it in an array
    if (isset($memberships_data['id'])) {
      $memberships_data = [$memberships_data,];
    }

    foreach ($memberships_data as $membership_data) {
      $site_name     = $membership_data['name'];
      $membership    = $membership_data['membership'];
      $membership_id = $membership_data['membership']['id'];

      //If site is not in the cache, add it as a new entry
      if (!isset($cache[$site_name])) {
        $cache[$site_name] = $this->getSiteData($membership_data);
      }

      //Then add the membership
      $cache[$site_name] = array_merge(
        $cache[$site_name],
        ['memberships' => [$membership_id => $membership,],]
      );
    }

    // and save the cache
    $this->cache->putData($this->cachekey, $cache);

    $sites = $this->all();
    return $sites;
  }

  /**
   * Returns all cached site data
   *
   * @return array
   */
  public function all() {
    $cache_data = $this->cache->getData(
      $this->cachekey,
      array('decode_array' => true)
    );
    if ($cache_data) {
      return $cache_data;
    } else {
      return array();
    }
  }

  /**
   * Clears cache data
   *
   * @return void
   */
  public function clear() {
    $this->cache->putData($this->cachekey, array());
  }

  /**
   * Finds the site of the given attributes within the cache
   *
   * @param string $name    Name of site to retrieve
   * @param array  $options Options to run array with
   *        [boolean] rebuild True to rebuild cache when run
   * @return array|null
   */
  private function find($name, $options = array()) {
    $defaults = array('rebuild' => true);
    $options  = array_merge($defaults, $options);
    $all      = $this->all();

    if (isset($all[$name])) {
      $site_data = $all[$name];
      return $site_data;
    } else {
      if ($options['rebuild']) {
        $this->rebuild();
        $recurse_without_rebuild = $this->find(
          $name,
          array('rebuild' => false)
        );
        return $recurse_without_rebuild;
      } else {
        return null;
      }
    }
  }

  /**
   * Searches the SitesCache for a UUID, given a name
   *
   * @param string $name    Name of site to retrieve UUID of
   * @param array  $options Options with which to find array, passed to find
   * @return string|null
   */
  public function findId($name, array $options = array()) {
    $site_data = $this->find($name, $options);
    if ($site_data) {
      $site_id = $site_data['id'];
      return $site_id;
    } else {
      return null;
    }
  }

  /**
   * Rebuilds sites cache
   *
   * @return array
   */
  public function rebuild() {
    // Clear out the cache
    $this->cache->putData($this->cachekey, array());

    // Add user's own sites
    $this->add($this->fetchUserSites());

    // Add all sites for each of user's organizations
    $orgs_data = $this->fetchUserOrganizations();

    foreach ($orgs_data as $org_data) {
      $this->add($this->fetchOrganizationSites($org_data));
    }

    $cached_sites = $this->all();
    return $cached_sites;
  }

  /**
   * Removes a site from the sites cache
   *
   * @param string $sitename Name of site to remove from array
   * @return void
   */
  public function remove($sitename) {
    $cache = (array)$this->cache->getData(
      $this->cachekey,
      array('decode_array' => true)
    );
    unset($cache[$sitename]);
    $this->cache->putData($this->cachekey, $cache);
  }

  /**
   * Updates a record with new information
   *
   * @param array $data Data from the API
   * @return void
   * @throws TerminusException
   */
  public function update($data = []) {
    if (!isset($data['name'])) {
      throw new TerminusException(
        'The new site data must include the name of the site.'
      );
    }
    $cache     = (array)$this->cache->getData(
      $this->cachekey,
      array('decode_array' => true)
    );
    $site_name = $data['name'];
    foreach ($data as $key => $value) {
      $cache[$site_name][$key] = $value;
    }
    $this->cache->putData($this->cachekey, $cache);
  }

  /**
   * Fetches organizational sites from API
   *
   * @param array $org_data Properties below:
   *        [string] id Organizaiton UUID
   * @return array $memberships_data
   */
  private function fetchOrganizationSites($org_data) {
    $response = $this->request->pagedRequest(
      'organizations/' . $org_data['id'] . '/memberships/sites'
    );

    $memberships_data = array();
    foreach ($response['data'] as $membership) {
      $site_data          = (array)$membership->site;
      $org_data['type']   = 'organization';
      $memberships_data[] = $this->getSiteData($site_data, $org_data);
    }

    return $memberships_data;
  }

  /**
   * Fetches organizational team-membership sites for user from API
   *
   * @return array
   */
  private function fetchUserSites() {
    $user_id  = Session::getValue('user_uuid');
    $response = $this->request->pagedRequest(
      'users/' . $user_id . '/memberships/sites'
    );

    $memberships_data = array();
    foreach ($response['data'] as $membership) {
      $site        = (array)$membership->site;
      $member_data = array(
        'id' => $user_id,
        'name' => 'Team',
        'type' => 'team'
      );
      $memberships_data[] = $this->getSiteData($site, $member_data);
    }

    return $memberships_data;
  }

  /**
   * Fetches organizational memberships for user
   *
   * @return array $data Properties below:
   *         [string] id   UUID of membership join
   *         [string] name Name of organization
   *         [string] type Always "organization"
   */
  private function fetchUserOrganizations() {
    $response = $this->request->pagedRequest(
      'users/' . Session::getValue('user_uuid') . '/memberships/organizations'
    );

    $data = array();
    foreach ($response['data'] as $membership) {
      if ($membership->role == 'unprivileged') {
        // Users with unprivileged role in organizations can't see organization
        // sites, but must be added to the team
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

  /**
   * Formats site data from response for use
   *
   * @param array $response_data   Data about the site from API
   * @param array $membership_data Data about membership to this site
   * @return array
   */
  private function getSiteData($response_data, $membership_data = array()) {
    $site_data = [
      'id'            => null,
      'name'          => null,
      'frozen'        => null,
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
      'membership'    => [],
    ];
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
