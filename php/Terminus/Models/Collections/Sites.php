<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Models\Site;
use Terminus\Models\User;
use Terminus\Session;

class Sites extends NewCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Site';

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Sites
   */
  public function __construct(array $options = []) {
    $this->user = Session::getUser();
    parent::__construct($options);
  }

  /**
   * Creates a new site
   *
   * @param string[] $options Parameters to run workflow, with the following
   *   keys:
   *   - label
   *   - name
   *   - organization_id
   *   - upstream_id
   * @return Workflow
   */
  public function create($options = []) {
    $params = [
      'label'     => $options['label'],
      'site_name' => $options['name']
    ];

    if (isset($options['organization_id'])) {
      $params['organization_id'] = $options['organization_id'];
    }

    if (isset($options['upstream_id'])) {
      $params['deploy_product'] = [
        'product_id' => $options['upstream_id']
      ];
    }
    
    $workflow = $this->user->workflows->create(
      'create_site',
      compact('params')
    );

    return $workflow;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $arg_options Parameters for the fetch request
   *        string  org_id    UUID for organization to fetch sites for
   *        boolean team_only True to only fetch team sites, not organizational
   * @return Sites
   */
  public function fetch(array $arg_options = []) {
    $default_options = [
      'org_id'    => null,
      'team_only' => false,
    ];
    $options         = array_merge($default_options, $arg_options);
    
    if (is_null($options['org_id'])) {
      $sites = $this->user->getSites();
      if (!$options['team_only']) {
        $organizations = $this->user->getOrganizations();
        foreach ($organizations as $organization) {
          $sites =
            array_merge($sites, $organization->getSites());
        }
      }
    } else {
      $this->user->org_memberships->fetch();
      $sites = $this->user->org_memberships->get($options['org_id'])
        ->organization->getSites();
    }
    foreach ($sites as $site) {
      if (!isset($this->models[$site->id])) {
        $site->collection        = $this;
        $this->models[$site->id] = $site;
      } else {
        $this->models[$site->id]->memberships[] = array_merge(
          $this->models[$site->id]->memberships,
          $site->memberships
        );
      }
    }
    return $this;
  }

  /**
   * Filters sites list by tag
   *
   * @param string $tag    Tag to filter by
   * @param string $org_id ID of an organization which has tagged sites
   * @return Sites
   */
  public function filterByTag($tag, $org_id) {
    $this->models = array_filter(
      $this->models,
      function($site) use ($tag, $org_id) {
        $has_tag = in_array($tag, $site->getTags($org_id));
        return $has_tag;
      }
    );
    return $this;
  }

  /**
   * Filters an array of sites by whether the user is an organizational member
   *
   * @param string $regex Non-delimited PHP regex to filter site names by
   * @return Sites
   */
  public function filterByName($regex = '(.*)') {
    $this->models = array_filter(
      $this->models,
      function($site) use ($regex) {
        preg_match("~$regex~", $site->get('name'), $matches);
        $is_match = !empty($matches);
        return $is_match;
      }
    );
    return $this;
  }

  /**
   * Filters an array of sites by whether the user is an organizational member
   *
   * @param string $owner_uuid UUID of the owning user to filter by
   * @return Sites
   */
  public function filterByOwner($owner_uuid) {
    $this->filter(['owner' => $owner_uuid,]);
    return $this;
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
    $models = $this->models;
    $list   = $this->list('name', 'id');
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
        ['id' => $uuid,],
        ['id' => $uuid, 'collection' => $this,]
      );
      $site->fetch();
      $this->models[$uuid] = $site;
    }
    return $site;
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

}
