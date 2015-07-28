<?php
namespace Terminus;

use Symfony\Component\Finder\Finder;
use Terminus;
use Terminus\FileCache;
use \Terminus_Command;
use Terminus\Session;

use Psy;

class SitesCache {
  protected $cache;
  protected $cachekey = 'sites';

  /**
   * Searches the SitesCache for an ID given a name
   *
   */
  static function find($name, $options = array()) {
    $instance = new SitesCache();
    return $instance->_find($name, $options);
  }

  public function __construct() {
    $this->cache = Terminus::get_cache();
  }

  public function _find($name, $options = array()) {
    if (!isset($options['rebuild'])) {
      $options['rebuild'] = true;
    }

    if (isset($this->all()[$name])) {
      return $this->all()[$name];
    } else {
      if ($options['rebuild']) {
        $this->rebuild();
        return $this->find($name, array('rebuild' => false));
      } else {
        return false;
      }
    }
  }

  public function all() {
    return (array) $this->cache->get_data($this->cachekey);
  }

  public function add($list = array()) {
    $data = array_merge(
      (array) $this->cache->get_data($this->cachekey),
      $list
    );
    $this->cache->put_data($this->cachekey, $data);

    return $this->all();
  }

  public function rebuild() {
    $this->cache->put_data($this->cachekey, array());
    $this->add($this->fetch_user_sites());

    $org_ids = array_keys($this->fetch_user_organizations());
    foreach ($org_ids as $org_id) {
      $this->add($this->fetch_organization_sites($org_id));
    }

    return $this->all();
  }

  public function fetch_user_sites() {
    $response = Terminus_Command::paged_request('users/' . Session::getValue('user_uuid') . '/memberships/sites');

    $data = array();
    foreach ($response['data'] as $membership) {
      $data[$membership->site->name] = $membership->id;
    }

    return $data;
  }

  public function fetch_user_organizations() {
    $response = Terminus_Command::paged_request('users/' . Session::getValue('user_uuid') . '/memberships/organizations');

    $data = array();
    foreach ($response['data'] as $membership) {
      if ($membership->role != 'unprivileged') {
        $data[$membership->id] = $membership->organization->profile->name;
      }
    }

    return $data;
  }

  public function fetch_organization_sites($organization_id) {
    $response = Terminus_Command::paged_request('organizations/' . $organization_id . '/memberships/sites');

    $data = array();
    foreach ($response['data'] as $membership) {
      $data[$membership->site->name] = $membership->id;
    }

    return $data;
  }
}
