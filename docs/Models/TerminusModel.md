# Terminus\Models\TerminusModel

### __construct
##### Description:
    Object constructor

##### Parameters:
    [stdClass] $attributes Attributes of this model
    [array]    $options    Options to set as $this->key

##### Return:
    [TerminusModel] $this

---

### __get
##### Description:
    Handles requests for inaccessable properties

##### Parameters:
    [string] $property Name of property being requested

##### Return:
    [mixed] $this->$property

---

### fetch
##### Description:
    Fetches this object from Pantheon

##### Parameters:
    [array] $options Params to pass to url request

##### Return:
    [TerminusModel] $this

---

### get
##### Description:
    Retrieves attribute of given name

##### Parameters:
    [string] $attribute Name of the key of the desired attribute

##### Return:
    [mixed] $this->attributes->$attribute

---

