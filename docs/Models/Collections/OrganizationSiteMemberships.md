# Terminus\Models\Collections\OrganizationSiteMemberships

### addMember
##### Description:
    Adds a site to this organization

##### Parameters:
    [Site] $site Site object of site to add to this organization

##### Return:
    [Workflow]

---

### get
##### Description:
    Retrieves the model with site of the given UUID or name

##### Parameters:
    [string] $id UUID or name of desired site membership instance

##### Return:
    [Site]

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [OrganizationSiteMemberships]

---

