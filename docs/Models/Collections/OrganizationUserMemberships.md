# Terminus\Models\Collections\OrganizationUserMemberships

### addMember
##### Description:
    Adds a user to this organization

##### Parameters:
    [string] $uuid UUID of user user to add to this organization
    [string] $role Role to assign to the new member

##### Return:
    [Workflow] $workflow

---

### get
##### Description:
    Retrieves models by either user ID, email address, or full name

##### Parameters:
    [string] $id Either a user ID, email address, or full name

##### Return:
    [OrganizationUserMembership]

##### Throws:
    TerminusException

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [OrganizationUserMemberships]

---

