# Terminus\Models\Site

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

---

### addInstrument
##### Description:
    Adds payment instrument of given site

##### Parameters:
    [string] $uuid UUID of new payment instrument

##### Return:
    [Workflow]

---

### addTag
##### Description:
    Adds a tag to the site

##### Parameters:
    [string] $tag    Name of tag to apply
    [string] $org_id Organization to add the tag association to

##### Return:
    [array]

---

### applyUpstreamUpdates
##### Description:
    Apply upstream updates

##### Parameters:
    [string] $env_id   Environment name
    [bool]   $updatedb True to run update.php
    [bool]   $xoption  True to automatically resolve merge conflicts

##### Return:
    [Workflow]

---

### attributes
##### Description:
    Returns an array of attributes

##### Return:
    [\stdClass]

---

### convergeBindings
##### Description:
    Converges all bindings on a site

##### Return:
    [array]

---

### createBranch
##### Description:
    Create a new branch

##### Parameters:
    [string] $branch Name of new branch

##### Return:
    [Workflow]

---

### delete
##### Description:
    Deletes site

##### Return:
    [array]

---

### deleteBranch
##### Description:
    Delete a branch from site remove

##### Parameters:
    [string] $branch Name of branch to remove

##### Return:
    [Workflow]

---

### deleteEnvironment
##### Description:
    Delete a multidev environment

##### Parameters:
    [string] $env           Name of environment to remove
    [bool]   $delete_branch True to delete branch

##### Return:
    [Workflow]

---

### deleteFromCache
##### Description:
    Deletes site from cache

##### Return:
    [void]

---

### disableRedis
##### Description:
    Disables Redis caching

##### Return:
    [array]

---

### disableSolr
##### Description:
    Disables Solr indexing

##### Return:
    [array]

---

### enableRedis
##### Description:
    Enables Redis caching

##### Return:
    [array]

---

### enableSolr
##### Description:
    Enables Solr indexing

##### Return:
    [array]

---

### fetch
##### Description:
    Fetches this object from Pantheon

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [Site]

---

### fetchAttributes
##### Description:
    Re-fetches site attributes from the API

##### Return:
    [void]

---

### get
##### Description:
    Returns given attribute, if present

##### Parameters:
    [string] $attribute Name of attribute requested

##### Return:
    [mixed|null] Attribute value, or null if not found

---

### getFeature
##### Description:
    Returns a specific site feature value

##### Parameters:
    [string] $feature Feature to check

##### Return:
    [mixed|null] Feature value, or null if not found

---

### getOrganizations
##### Description:
    Returns all organization members of this site

##### Return:
    [SiteOrganizationMembership[]]

---

### getSiteUserMemberships
##### Description:
    Lists user memberships for this site

##### Return:
    [SiteUserMemberships]

---

### getTags
##### Description:
    Returns tags from the site/org join

##### Parameters:
    [string] $org_id UUID of organization site belongs to

##### Return:
    [string[]]

---

### getTips
##### Description:
    Just the code branches

##### Return:
    [array]

---

### getUpstreamUpdates
##### Description:
    Get upstream updates

##### Return:
    [\stdClass]

---

### hasTag
##### Description:
    Checks to see whether the site has a tag associated with the given org

##### Parameters:
    [string] $tag    Name of tag to check for
    [string] $org_id Organization with which this tag is associated

##### Return:
    [bool]

---

### import
##### Description:
    Imports a full-site archive

##### Parameters:
    [string] $url URL to import data from

##### Return:
    [Workflow]

---

### info
##### Description:
    Load site info

##### Parameters:
    [string] $key Set to retrieve a specific attribute as named

##### Return:
    [array|null|mixed]
    -If $key is supplied, return named bit of info, or null if not found.
    -If no $key supplied, return entire info array.

---

### newRelic
##### Description:
    Retrieve New Relic Info

##### Return:
    [\stdClass]

---

### organizationIsMember
##### Description:
    Determines if an organization is a member of this site

##### Parameters:
    [string] $uuid UUID of organization to check for

##### Return:
    [bool] True if organization is a member of this site

---

### removeInstrument
##### Description:
    Removes payment instrument of given site

##### Return:
    [Workflow]

---

### removeTag
##### Description:
    Removes a tag to the site

##### Parameters:
    [string] $tag    Tag to remove
    [string] $org_id Organization to remove the tag association from

##### Return:
    [array]

---

### setOwner
##### Description:
    Sets the site owner to the indicated team member

##### Parameters:
    [string] $owner UUID of new owner of site

##### Return:
    [Workflow]

##### Throws:
    TerminusException

---

### setPhpVersion
##### Description:
    Sets the PHP version number of this site
    Note: Once this changes, you need to refresh the data in the cache for
    this site or the returned PHP version will not reflect the change.
    $this->fetchAttributes() will complete this action for you.

##### Parameters:
    [string] $version_number The version number to set this site to use

##### Return:
    [void]

---

### updateServiceLevel
##### Description:
    Update service level

##### Parameters:
    [string] $level Level to set service on site to

##### Return:
    [\stdClass]

##### Throws:
    TerminusException

---

