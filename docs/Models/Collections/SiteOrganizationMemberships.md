# Terminus\Models\Collections\SiteOrganizationMemberships

### addMember
##### Description:
    Adds this org as a member to the site

##### Parameters:
    [string] $name Name of site to add org to
    [string] $role Role for supporting organization to take

##### Return:
    [Workflow] $workflow

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [TerminusModel] $this

---

### findByName
##### Description:
    Returns UUID of organization with given name

##### Parameters:
    [string] $name A name to search for

##### Return:
    [SiteOrganizationMembership] $orgs[$name]

---

