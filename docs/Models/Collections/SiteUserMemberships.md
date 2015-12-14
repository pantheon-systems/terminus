# Terminus\Models\Collections\SiteUserMemberships

### addMember
##### Description:
    Adds this user as a member to the site

##### Parameters:
    [string] $email Email of team member to add
    [string] $role  Role to assign to the new user

##### Return:
    [workflow] $workflow

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [SiteUserMemberships] $this

---

### get
##### Description:
    Retrieves the membership of the given UUID or email

##### Parameters:
    [string] $id UUID or email of desired user

##### Return:
    [SiteUserMembership] $membership

---

