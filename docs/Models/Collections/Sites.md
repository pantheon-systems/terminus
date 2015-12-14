# Terminus\Models\Collections\Sites

### __construct
##### Description:
    Instantiates the collection, sets param members as properties

##### Parameters:
    [array] $options To be set to $this->key

##### Return:
    [Sites] $this

---

### addSite
##### Description:
    Creates a new site

##### Parameters:
    [array] $options Information to run workflow
    -[string] label
    -[string] name
    -[string] organization_id
    -[string] upstream_id

##### Return:
    [Workflow]

---

### addSiteToCache
##### Description:
    Adds site with given site ID to cache

##### Parameters:
    [string] $site_id UUID of site to add to cache

##### Return:
    [Site] $site The newly created site object

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
    [Sites] $this

---

### filterAllByTag
##### Description:
    Filters sites list by tag

##### Parameters:
    [string] $tag Tag to filter by
    [string] $org Organization which has tagged sites

##### Return:
    [array] $sites A filtered list of sites

---

### get
##### Description:
    Retrieves the site of the given UUID or name

##### Parameters:
    [string] $id UUID or name of desired site

##### Return:
    [Site] $site

---

### rebuildCache
##### Description:
    Clears sites cache

##### Return:
    [void]

---

