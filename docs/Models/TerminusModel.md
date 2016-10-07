# Terminus\Models\TerminusModel

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

### get
##### Description:
    Retrieves attribute of given name

##### Parameters:
    [string] $attribute Name of the key of the desired attribute

##### Return:
    [mixed] Value of the attribute, or null if not set.

---

### has
##### Description:
    Checks whether the model has an attribute

##### Parameters:
    [string] $attribute Name of the attribute key

##### Return:
    [boolean] True if attribute exists, false otherwise

---

### set
##### Description:
    Sets an attribute

##### Parameters:
    [string] $attribute Name of the attribute key
    [mixed]  $value     The value to assign to the attribute

---

