<?php

namespace Terminus\Models\Collections;

use Terminus\Session;
use Terminus\SitesCache;
use Terminus\Models\Site;
use Terminus\Models\User;
use Terminus\Models\Collections\TerminusCollection;

class Sites extends TerminusCollection {
  private $sites_cache;
  private $user;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param [array] $options To be set to $this->key
   * @return [Sites] $this
   */
  public function __construct($options = array()) {
    parent::__construct($options);
    $this->sites_cache = new SitesCache();
    $this->user        = new User();
  }

  /**
   * Creates a new site
   *
   * @param [array] $options Information to run workflow
   *        [string] label
   *        [string] name
   *        [string] organization_id
   *        [string] upstream_id
   * @return [Workflow]
   */
  public function addSite($options = array()) {
    $data = array(
      'label'     => $options['label'],
      'site_name' => $options['name']
    );

    if (isset($options['organization_id'])) {
      $data['organization_id'] = $options['organization_id'];
    }

    if (isset($options['upstream_id'])) {
      $data['deploy_product'] = array(
        'product_id' => $options['upstream_id']
      );
    }

    $workflow = $this->user->workflows->create(
      'create_site',
      array('params' => $data)
    );

    return $workflow;
  }

  /**
   * Adds site with given site ID to cache
   *
   * @param [string] $site_id UUID of site to add to cache
   * @return [Site] $site The newly created site object
   */
  public function addSiteToCache($site_id) {
    if (count($this->models) == 0) {
      $this->rebuildCache();
      $site = $this->get($site_id);
    } else {
      $site = new Site(
        $this->objectify(array('id' => $site_id)),
        array('collection' => $this)
      );
      $site->fetch();
      $cache_membership = $site->info();

      if ($org_id) {
        $org = new Organization(null, array('id' => $org_id));
        $cache_membership['membership'] = array(
          'id' => $org_id,
          'name' => $org->profile->name,
          'type' => 'organization'
        );
      } else {
        $user_id = Session::getValue('user_uuid');
        $cache_membership['membership'] = array(
          'id' => $user_id,
          'name' => 'Team',
          'type' => 'team'
        );
      }
      $this->sites_cache->add($cache_membership);
    }
    return $site;
  }

  /**
   * Removes site with given site ID from cache
   *
   * @param [string] $site_name Name of site to remove from cache
   * @return [void]
   */
  public function deleteSiteFromCache($site_name) {
    $this->sites_cache->remove($site_name);
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [Sites] $this
   */
  public function fetch($paged = false) {
    if (empty($this->models)) {
      $cache = $this->sites_cache->all();
      if (count($cache) === 0) {
        $this->rebuildCache();
        $cache = $this->sites_cache->all();
      }
      foreach ($cache as $name => $model) {
        $this->add($this->objectify($model));
      }
    }
    return $this;
  }

  /**
   * Retrieves the site of the given UUID or name
   *
   * @param [string] $id UUID or name of desired site
   * @return [Site] $site
   */
  public function get($id) {
    $models = $this->getMembers();
    $list   = $this->getMemberList('name', 'id');
    $site   = null;
    if (isset($models[$id])) {
      $site = $models[$id];
    } elseif (isset($list[$id])) {
      $site = $models[$list[$id]];
    }
    if ($site == null) {
      throw new \Exception(sprintf('Cannot find site with the name "%s"', $id));
    }
    return $site;
  }

  /**
   * Clears sites cache
   *
   * @return [void]
   */
  public function rebuildCache() {
    $this->sites_cache->rebuild();
  }

}
