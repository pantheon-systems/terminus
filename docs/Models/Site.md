# Terminus\Models\Site

### __construct
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
    [string]       $tag Name of tag to apply
    [Organization] $org Organization to add the tag association to

##### Return:
    [array]

---

### deployProduct
##### Description:
    Creates a new site for migration

##### Parameters:
    [string[]] $product_id The uuid for the product to deploy.

##### Return:
    [Workflow]

---

### completeMigration
##### Description:
    Completes a site migration in progress

##### Return:
    [Workflow]

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
    Deletes the site represented by this object

---

### deleteBranch
##### Description:
    Delete a branch from site remove

##### Parameters:
    [string] $branch Name of branch to remove

##### Return:
    [Workflow]

---

### disableNewRelic
##### Description:
    Disables New Relic

##### Parameters:
    [object] $site The site object

##### Return:
    [bool]

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

### enableNewRelic
##### Description:
    Enables New Relic

##### Parameters:
    [object] $site The site object

##### Return:
    [bool]

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

### getTags
##### Description:
    Returns tags from the site/org join
    TODO: Move these into tags model/collection

##### Parameters:
    [Organization] $org UUID of organization site belongs to

##### Return:
    [string[]]

---

### getTips
##### Description:
    Just the code branches

##### Return:
    [array]

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

### newRelic
##### Description:
    Retrieve New Relic Info

##### Return:
    [\stdClass]

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
    [string]       $tag Tag to remove
    [Organization] $org Organization to remove the tag association from

##### Return:
    [array]

---

### serialize
##### Description:
    Formats the Site object into an associative array for output

##### Return:
    [array] Associative array of data for output

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

### updateServiceLevel
##### Description:
    Update service level

##### Parameters:
    [string] $level Level to set service on site to

##### Return:
    [Workflow]

##### Throws:
    TerminusException

---

