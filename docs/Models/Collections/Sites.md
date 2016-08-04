# Terminus\Models\Collections\Sites

### __construct
##### Description:
    Instantiates the collection, sets param members as properties

##### Parameters:
    [array] $options To be set to $this->key

##### Return:
    [Sites]

---

### addSite
##### Description:
    Creates a new site

##### Parameters:
    [string[]] $params Options for the new site, elements as follow:
    -string label The site's human-friendly name
    -string site_name The site's name
    -string organization_id Organization to which this site belongs' UUID
    -string type Workflow type for imports
    -string upstream_id If the upstream's UUID absent, the site is migratory.

##### Return:
    [Workflow]

---

### addSiteToCache
##### Description:
    Adds site with given site ID to cache

##### Parameters:
    [string] $site_id UUID of site to add to cache
    [string] $org_id  UUID of org to which new site belongs

##### Return:
    [Site] The newly created site object

---

### deleteSiteFromCache
##### Description:
    Removes site with given site ID from cache

##### Parameters:
    [string] $site_name Name of site to remove from cache

##### Return:
    [void]

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [Sites]

---

### filterAllByTag
##### Description:
    Filters sites list by tag

##### Parameters:
    [string] $tag Tag to filter by
    [string] $org Organization which has tagged sites

##### Return:
    [Site[]]

##### Throws:
    TerminusException

---

### findUuidByName
##### Description:
    Looks up a site's UUID by its name.

##### Parameters:
    [string] $name Name of the site to look up

##### Return:
    [string]

---

### get
##### Description:
    Retrieves the site of the given UUID or name

##### Parameters:
    [string] $id UUID or name of desired site

##### Return:
    [Site]

##### Throws:
    TerminusException

---

### rebuildCache
##### Description:
    Clears sites cache

##### Return:
    [void]

---

