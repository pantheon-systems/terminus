# Terminus\Models\Collections\Sites

### __construct
##### Description:
    Instantiates the collection, sets param members as properties

##### Parameters:
    [array] $options To be set to $this->key

##### Return:
    [Sites]

---

### all
##### Description:
    Retrieves all sites

##### Return:
    [Site[]]

---

### create
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

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $arg_options params to pass to url request

##### Return:
    [Sites]

---

### filterByTag
##### Description:
    Filters sites list by tag

##### Parameters:
    [string] $tag    Tag to filter by
    [string] $org_id ID of an organization which has tagged sites

##### Return:
    [Sites]

---

### filterByName
##### Description:
    Filters an array of sites by whether the user is an organizational member

##### Parameters:
    [string] $regex Non-delimited PHP regex to filter site names by

##### Return:
    [Sites]

---

### filterByOwner
##### Description:
    Filters an array of sites by whether the user is an organizational member

##### Parameters:
    [string] $owner_uuid UUID of the owning user to filter by

##### Return:
    [Sites]

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

### nameIsTaken
##### Description:
    Determines whether a given site name is taken or not.

##### Parameters:
    [string] $name Name of the site to look up

##### Return:
    [boolean]

---

