<?php

namespace Terminus\Models\Collections;

use Terminus\Session;
use Terminus\Caches\SitesCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Organization;
use Terminus\Models\Site;
use Terminus\Models\User;
use Terminus\Models\Workflow;

class Sites extends TerminusCollection {
  /**
   * @var SitesCache
   */
  public $sites_cache;
  /**
   * @var User
   */
  private $user;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param array $options To be set to $this->key
   * @return Sites
   */
  public function __construct(array $options = array()) {
    parent::__construct($options);
    $this->sites_cache = new SitesCache();
    $this->user        = Session::getUser();
  }

  /**
   * Creates a new site
   *
   * @param string[] $options Information to run workflow, with the following
   *   keys:
   *   - label
   *   - name
   *   - organization_id
   *   - upstream_id
   * @return Workflow
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
    
    if ($this->nameIsTaken($data['site_name'])) {
      throw new TerminusException(
        'The name {site_name} is taken. Please select another name.',
        ['site_name' => $data['site_name'],],
        1
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
   * @param string $site_id UUID of site to add to cache
   * @param string $org_id  UUID of org to which new site belongs
   * @return Site The newly created site object
   */
  public function addSiteToCache($site_id, $org_id = null) {
    $site = new Site(
      (object)['id' => $site_id,],
      ['collection' => $this,]
    );
    $site->fetch();
    $cache_membership = $site->info();

    if (!is_null($org_id)) {
      $org = new Organization(null, ['id' => $org_id,]);
      $cache_membership['membership'] = [
        'id' => $org_id,
        'name' => $org->profile->name,
        'type' => 'organization',
      ];
    } else {
      $user_id = Session::getValue('user_uuid');
      $cache_membership['membership'] = [
        'id' => $user_id,
        'name' => 'Team',
        'type' => 'team',
      ];
    }
    $this->sites_cache->add($cache_membership);
    return $site;
  }

  /**
    * Removes site with given site ID from cache
   *
   * @param string $site_name Name of site to remove from cache
   * @return void
   */
  public function deleteSiteFromCache($site_name) {
    $this->sites_cache->remove($site_name);
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return Sites
   */
  public function fetch(array $options = array()) {
    if (empty($this->models)) {
      $cache = $this->sites_cache->all();
      if (count($cache) === 0) {
        $this->rebuildCache();
        $cache = $this->sites_cache->all();
      }
      foreach ($cache as $name => $model) {
        $this->add((object)$model);
      }
    }
    return $this;
  }

  /**
   * Filters sites list by tag
   *
   * @param string $tag Tag to filter by
   * @param string $org Organization which has tagged sites
   * @return Site[]
   * @throws TerminusException
   */
  public function filterAllByTag($tag, $org = '') {
    $all_sites = $this->all();
    if (!$tag) {
      return $all_sites;
    }

    $sites = array();
    foreach ($all_sites as $id => $site) {
      if ($site->organizationIsMember($org)) {
        $tags = $site->getTags($org);
        if (in_array($tag, $tags)) {
          $sites[$id] = $site;
        }
      }
    }
    if (empty($sites)) {
      throw new TerminusException(
        'No sites associated with {org} had the tag {tag}.',
        array('org' => $org, 'tag' => $tag),
        1
      );
    }
    return $sites;
  }

  /**
   * Looks up a site's UUID by its name.
   *
   * @param string $name Name of the site to look up
   * @return string
   */
  public function findUuidByName($name) {
    $response = $this->request->request(
      "site-names/$name",
      ['method' => 'get',]
    );
    return $response['data'];
  }

  /**
   * Retrieves the site of the given UUID or name
   *
   * @param string $id UUID or name of desired site
   * @return Site
   * @throws TerminusException
   */
  public function get($id) {
    $models = $this->getMembers();
    $list   = $this->getMemberList('name', 'id');
    $site   = null;
    if (isset($models[$id])) {
      $site = $models[$id];
    } elseif (isset($list[$id])) {
      $site = $models[$list[$id]];
    } else {
      try {
        $uuid = $this->findUuidByName($id)->id;
      } catch (\Exception $e) {
        throw new TerminusException(
          'Could not locate a site your user may access identified by {id}.',
          compact('id'),
          1
        );
      }
      $site = new Site(
        (object)['id' => $uuid,],
        ['id' => $uuid, 'collection' => $this,]
      );
      $site->fetch();
      $this->models[$uuid] = $site;
    }
    return $site;
  }

  /**
   * Clears sites cache
   *
   * @return void
   */
  public function rebuildCache() {
    $this->sites_cache->rebuild();
  }

  /**
   * Determines whether a given site name is taken or not.
   *
   * @param string $name Name of the site to look up
   * @return boolean
   */
  public function nameIsTaken($name) {
    try {
      $this->findUuidByName($name);
    } catch (\Exception $e) {
      //We're using this to assign the exception to $e.
    }
    $name_is_taken = (
      !isset($e) || (strpos($e->getMessage(), '404 Not Found') === false)
    );
    return $name_is_taken;
  }

  /**
   * Retrieves all members of this collection
   *
   * @return Site[]
   */
  protected function getMembers() {
    return $this->models;
  }
  
}
