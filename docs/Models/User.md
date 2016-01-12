# Terminus\Models\User

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

---

### parseAttributes
##### Description:
    Modify response data between fetch and assignment

##### Parameters:
    [object] $data attributes received from API response

##### Return:
    [object] $data

---

### getAliases
##### Description:
    Retrieves drush aliases for this user

##### Return:
    [\stdClass]

---

### getOrganizations
##### Description:
    Retrieves organization data for this user

##### Return:
    [Organization[]]

---

### getSites
##### Description:
    Requests API data and returns an object of user site data

##### Parameters:
    [string] $organization UUID of organization to requests sites from,
    -or null to fetch for all organizations.

##### Return:
    [\stdClass]

---

### serialize
##### Description:
    Formats User object into an associative array for output

##### Return:
    [array] $data associative array of data for output

---

