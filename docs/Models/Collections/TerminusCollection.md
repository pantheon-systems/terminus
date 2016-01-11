# Terminus\Models\Collections\TerminusCollection

### __construct
##### Description:
    Instantiates the collection, sets param members as properties

##### Parameters:
    [array] $options To be set to $this->key

---

### all
##### Description:
    Retrieves all models

##### Return:
    [TerminusModel[]]

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [TerminusCollection] $this

---

### get
##### Description:
    Retrieves the model of the given ID

##### Parameters:
    [string] $id ID of desired model instance

##### Return:
    [TerminusModel] $this->models[$id]

##### Throws:
    TerminusException

---

### ids
##### Description:
    List Model IDs

##### Return:
    [string[]] Array of all model IDs

---

### add
##### Description:
    Adds a model to this collection

##### Parameters:
    [object] $model_data Data to feed into attributes of new model
    [array]  $options    Data to make properties of the new model

##### Return:
    [TerminusModel]

---

### getFilteredMemberList
##### Description:
    Returns an array of data where the keys are the attribute $key and the
    values are the attribute $value, filtered by the given array

##### Parameters:
    [array]        $filters Attributes to match during filtration
    -e.g. array('category' => 'other')
    [string]       $key     Name of attribute to make array keys
    [string|array] $value   Name(s) of attribute to make array values

##### Return:
    [array] Array rendered as requested
    -$this->attribute->$key = $this->attribute->$value

---

### getMemberList
##### Description:
    Returns an array of data where the keys are the attribute $key and the
    values are the attribute $value

##### Parameters:
    [string] $key   Name of attribute to make array keys
    [string] $value Name of attribute to make array values

##### Return:
    [array] Array rendered as requested
    -$this->attribute->$key = $this->attribute->$value

---

