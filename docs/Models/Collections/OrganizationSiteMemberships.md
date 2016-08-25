# Terminus\Models\Collections\OrganizationSiteMemberships

### __construct
##### Description:
    Instantiates the collection

##### Parameters:
    [array] $options To be set

##### Return:
    [OrganizationSiteMemberships]

---

### add
##### Description:
    Adds a model to this collection

##### Parameters:
    [object] $model_data  Data to feed into attributes of new model
    [array]  $arg_options Data to make properties of the new model

##### Return:
    [void]

---

### create
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
    [OrganizationSiteMembership]

---

### getSite
##### Description:
    Retrieves the matching site from model members

##### Parameters:
    [string] $site_id ID or name of desired site

##### Return:
    [Site] $site

##### Throws:
    TerminusException

---

### siteIsMember
##### Description:
    Determines whether a site is a member of this collection

##### Parameters:
    [Site] $site Site to determine membership of

##### Return:
    [bool]

---

