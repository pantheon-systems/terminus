# Terminus\Models\Collections\UserOrganizationMemberships

### __construct
##### Description:
    Object constructor

##### Parameters:
    [array] $options Options to set as $this->key

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

### getOrganization
##### Description:
    Retrieves the matching organization from model members

##### Parameters:
    [string] $org ID or name of desired organization

##### Return:
    [Organization] $organization

##### Throws:
    TerminusException

---

