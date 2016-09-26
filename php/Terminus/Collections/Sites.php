<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Session;

class Sites extends TerminusCollection
{
  /**
   * @var string
   */
    protected $collected_class = 'Terminus\Models\Site';
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
    public function __construct(array $options = [])
    {
        $this->user = Session::getUser();
        parent::__construct($options);
    }

  /**
   * Retrieves all sites
   *
   * @return Site[]
   */
    public function all()
    {
        $models = array_values($this->models);
        return $models;
    }

  /**
   * Creates a new site
   *
   * @param string[] $params Options for the new site, elements as follow:
   *   string label The site's human-friendly name
   *   string site_name The site's name
   *   string organization_id Organization to which this site belongs' UUID
   * @return Workflow
   */
    public function create($params = [])
    {
        $workflow = $this->user->workflows->create('create_site', compact('params'));
        return $workflow;
    }

  /**
   * Creates a new site for migration
   *
   * @param string[] $params Options for the new site, elements as follow:
   *   string label The site's human-friendly name
   *   string site_name The site's name
   *   string organization_id Organization to which this site belongs' UUID
   *   string type Workflow type for imports
   * @return Workflow
   */
    public function createForMigration($params = [])
    {
        $workflow = $this->user->workflows->create('create_site_for_migration', compact('params'));
        return $workflow;
    }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $arg_options Options to change the requests made. Elements as follow:
   *        string  org_id    UUID of the organization to retrieve sites for
   *        boolean team_only True to only retrieve team sites
   * @return Sites
   */
    public function fetch(array $arg_options = [])
    {
        $default_options = [
        'org_id'    => null,
        'team_only' => false,
        ];
        $options = array_merge($default_options, $arg_options);

        $sites = [];
        if (is_null($options['org_id'])) {
            $sites[] = $this->user->getSites();
        }
        if (!$options['team_only']) {
            $memberships = $this->user->org_memberships->fetch()->all();
            if (!is_null($org_id = $options['org_id']) && ($org_id != 'all')) {
                $memberships = [$memberships[$org_id],];
            }
            foreach ($memberships as $membership) {
                if ($membership->get('role') != 'unprivileged') {
                    $sites[] = $membership->organization->getSites();
                }
            }
        }
        $merged_sites = [];
        foreach ($sites as $site_group) {
            foreach ($site_group as $site) {
                if (!isset($merged_sites[$site->id])) {
                    $merged_sites[$site->id] = $site;
                } else {
                    $merged_sites[$site->id]->memberships[] = $site->memberships[0];
                }
            }
        }
        $this->models = $merged_sites;

        return $this;
    }

  /**
   * Filters sites list by tag
   *
   * @param string $tag    Tag to filter by
   * @param string $org_id ID of an organization which has tagged sites
   * @return Sites
   */
    public function filterByTag($tag, $org_id)
    {
        $this->models = array_filter(
            $this->models,
            function ($site) use ($tag, $org_id) {
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
    public function filterByName($regex = '(.*)')
    {
        $this->models = array_filter(
            $this->models,
            function ($site) use ($regex) {
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
    public function filterByOwner($owner_uuid)
    {
        $this->filter(['owner' => $owner_uuid,]);
        return $this;
    }

  /**
   * Looks up a site's UUID by its name.
   *
   * @param string $name Name of the site to look up
   * @return string
   */
    public function findUuidByName($name)
    {
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
    public function get($id)
    {
        $models = $this->models;
        $list   = $this->listing('name', 'id');
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
            $site = new $this->collected_class(
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
    public function nameIsTaken($name)
    {
        try {
            $this->findUuidByName($name);
            //If this has not been caught, the name is taken.
            $name_is_taken = true;
        } catch (\Exception $e) {
            $name_is_taken = strpos($e->getMessage(), '404 Not Found') !== false;
        }
        return $name_is_taken;
    }
}
