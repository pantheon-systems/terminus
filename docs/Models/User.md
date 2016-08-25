# Terminus\Models\User

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

---

### getAliases
##### Description:
    Retrieves Drush aliases for this user

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

##### Return:
    [Site[]]

---

### serialize
##### Description:
    Formats User object into an associative array for output

##### Return:
    [array] $data associative array of data for output

---

