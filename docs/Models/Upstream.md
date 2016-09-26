# Terminus\Models\Upstream

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options with which to configure this model

---

### fetch
##### Description:
    Fetches this object from Pantheon

##### Parameters:
    [array] $args Params to pass to request

##### Return:
    [TerminusModel] $this

---

### getStatus
##### Description:
    Returns the status of this site's upstream updates

##### Return:
    [string] $status 'outdated' or 'current'

---

### getUpdates
##### Description:
    Retrives upstream updates

##### Return:
    [\stdClass]

---

### hasUpdates
##### Description:
    Determines whether there are any updates to be applied.

##### Return:
    [boolean]

---

### serialize
##### Description:
    Formats the Upstream object into an associative array for output

##### Return:
    [array] Associative array of data for output

---

