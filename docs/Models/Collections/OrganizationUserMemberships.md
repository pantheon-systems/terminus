# Terminus\Models\Collections\OrganizationUserMemberships

### __construct
##### Description:
    Instantiates the collection, sets param members as properties

##### Parameters:
    [array] $options To be set to $this->key

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

