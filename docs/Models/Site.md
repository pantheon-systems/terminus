# Terminus\Models\Site

### __construct
##### Description:
    Object constructor

##### Parameters:
    [stdClass] $attributes Attributes of this model
    [array]    $options    Options to set as $this->key

##### Return:
    [Site] $this

---

### addInstrument
##### Description:
    Adds payment instrument of given site

##### Parameters:
    [string] $uuid UUID of new payment instrument

##### Return:
    [Workflow] $workflow Workflow object for the request

---

### addTag
##### Description:
    Adds a tag to the site

##### Parameters:
    [string] $tag Tag to apply
    [string] $org Organization to add the tag associateion to

##### Return:
    [array] $response

---

### applyUpstreamUpdates
##### Description:
    Apply upstream updates

##### Parameters:
    [string]  $env_id   Environment name
    [boolean] $updatedb True to run update.php
    [boolean] $xoption  True to automatically resolve merge conflicts

##### Return:
    [Workflow] $workflow

---

### attributes
##### Description:
    Returns an array of attributes

##### Return:
    [stdClass] $atts['data']

---

### bindings
##### Description:
    Fetch Binding info

##### Parameters:
    [string] $type Which sort of binding to retrieve

##### Return:
    [array] $this->bindings

---

### createBranch
##### Description:
    Create a new branch

##### Parameters:
    [string] $branch Name of new branch

##### Return:
    [Workflow] $workflow

---

### delete
##### Description:
    Deletes site

##### Return:
    [array] $response

---

### deleteBranch
##### Description:
    Delete a branch from site remove

##### Parameters:
    [string] $branch Name of branch to remove

##### Return:
    [void]

---

### deleteEnvironment
##### Description:
    Delete a multidev environment

##### Parameters:
    [string]  $env           Name of environment to remove
    [boolean] $delete_branch True to delete branch

##### Return:
    [void]

---

### deleteFromCache
##### Description:
    Deletes site from cache

##### Return:
    [void]

---

### fetch
##### Description:
    Fetches this object from Pantheon

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [Site] $this

---

### get
##### Description:
    Returns given attribute, if present

##### Parameters:
    [string] $attribute Name of attribute requested

##### Return:
    [mixed] $this->attributes->$attributes;

---

### getFeature
##### Description:
    Returns a specific site feature value

##### Parameters:
    [string] $feature Feature to check

##### Return:
    [mixed] $this->features[$feature]

---

### getOrganizations
##### Description:
    Returns all organization members of this site

##### Return:
    [array] Array of SiteOrganizationMemberships

---

### getSiteUserMemberships
##### Description:
    Lists user memberships for this site

##### Return:
    [SiteUserMemberships] Collection of user memberships for this site

---

### getTags
##### Description:
    Returns tags from the site/org join

##### Parameters:
    [string] $org UUID of organization site belongs to

##### Return:
    [array] $tags Tags in string format

---

### getUpstreamUpdates
##### Description:
    Get upstream updates

##### Return:
    [stdClass] $response['data']

---

### import
##### Description:
    Imports a full-site archive

##### Parameters:
    [string] $url URL to import data from

##### Return:
    [Workflow] $workflow

---

### importDatabase
##### Description:
    Imports a database archive

##### Parameters:
    [string] $url URL to import data from

##### Return:
    [Workflow] $workflow

---

### importFiles
##### Description:
    Imports a file archive

##### Parameters:
    [string] $url URL to import data from

##### Return:
    [Workflow] $workflow

---

### info
##### Description:
    Load site info

##### Parameters:
    [string] $key Set to retrieve a specific attribute as named

##### Return:
    [array] $info

---

### newRelic
##### Description:
    Retrieve New Relic Info

##### Return:
    [stdClass] $response['data']

---

### organizationIsMember
##### Description:
    Returns all organization members of this site

##### Parameters:
    [string] $uuid UUID of organization to check for

##### Return:
    [boolean] True if organization is a member of this site

---

### removeInstrument
##### Description:
    Removes payment instrument of given site

##### Return:
    [Workflow] $workflow Workflow object for the request

---

### removeTag
##### Description:
    Removes a tag to the site

##### Parameters:
    [string] $tag Tag to remove
    [string] $org Organization to remove the tag associateion from

##### Return:
    [array] $response

---

### setOwner
##### Description:
    Owner handler

##### Parameters:
    [string] $owner UUID of new owner of site

##### Return:
    [stdClass] $data['data']

---

### tips
##### Description:
    Just the code branches

##### Return:
    [stdClass] $data['data']

---

### updateServiceLevel
##### Description:
    Update service level

##### Parameters:
    [string] $level Level to set service on site to

##### Return:
    [stdClass] $response['data']

---

