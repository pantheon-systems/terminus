# Terminus\Models\User

### __construct
##### Description:
    Object constructor

##### Parameters:
    [\stdClass] $attributes Attributes of this model
    [array]     $options    Options to set as $this->key

---

### getAliases
##### Description:
    Retrieves drush aliases for this user

##### Return:
    [\stdClass] $this->aliases

---

### getOrganizations
##### Description:
    Retrieves organization data for this user

##### Return:
    [\stdClass] $organizations

---

### getSites
##### Description:
    Requests API data and returns an object of user site data

##### Parameters:
    [string] $organization UUID of organization to requests sites from,
    -or null to fetch for all organizations.

##### Return:
    [\stdClass] $response['data']

---

