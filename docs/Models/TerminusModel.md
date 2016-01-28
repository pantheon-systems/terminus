# Terminus\Models\TerminusModel

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

---

### __get
##### Description:
    Handles requests for inaccessible properties

##### Parameters:
    [string] $property Name of property being requested

##### Return:
    [mixed] $this->$property

##### Throws:
    TerminusException

---

### fetch
##### Description:
    Fetches this object from Pantheon

##### Parameters:
    [array] $options Params to pass to url request

##### Return:
    [TerminusModel] $this

---

### parseAttributes
##### Description:
    Modify response data between fetch and assignment

##### Parameters:
    [object] $data attributes received from API response

##### Return:
    [object] $data

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

