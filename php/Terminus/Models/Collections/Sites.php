<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Models\Site;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Session;

class Sites extends TerminusCollection {
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
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return Sites
   */
  public function fetch(array $options = array()) {
    if (empty($this->models)) {
      $sites = $this->rebuild();
      foreach ($sites as $name => $model) {
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

  public function rebuild() {
    $sites = $this->fetchUserSites();

    // Add all sites for each of user's organizations
    $orgs_data = $this->fetchUserOrganizations();

    foreach ($orgs_data as $org_data) {
      $sites = array_merge($this->fetchOrganizationSites($org_data), $sites);
    }
    
    foreach($sites as $site) {
      $this->add((object)$site);
    }

    return $this;
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
