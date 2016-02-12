# Terminus\Models\Organization

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

---

### getFeature
##### Description:
    Returns a specific organization feature value

##### Parameters:
    [string] $feature Feature to check

##### Return:
    [mixed|null] Feature value, or null if not found

---

### getSites
##### Description:
    Retrieves organization sites

##### Return:
    [OrganizationSiteMembership[]]

---

### getUsers
##### Description:
    Retrieves organization users

##### Return:
    [OrganizationUserMembership[]]

---

