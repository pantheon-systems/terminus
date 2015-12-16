# Terminus\Models\Collections\Workflows

### create
##### Description:
    Creates a new workflow and adds its data to the collection

##### Parameters:
    [string] $type    Type of workflow to create
    [array]  $options Additional information for the request, with the
    -following possible keys:
    -- environment: string
    -- params: associative array of parameters for the request

##### Return:
    [Workflow] $model

---

### fetchWithOperations
##### Description:
    Fetches workflow data hydrated with operations

##### Parameters:
    [array] $options Additional information for the request

##### Return:
    [void]

---

### allFinished
##### Description:
    Returns all existing workflows that have finished

##### Return:
    [Workflow[]]

---

### allWithLogs
##### Description:
    Returns all existing workflows that contain logs

##### Return:
    [Workflow[]]

---

### lastFinishedAt
##### Description:
    Get timestamp of most recently finished workflow

##### Return:
    [int|null] Timestamp

---

### lastCreatedAt
##### Description:
    Get timestamp of most recently created Workflow

##### Return:
    [int|null] Timestamp

---

### findLatestWithLogs
##### Description:
    Get most-recent workflow from existing collection that has logs

##### Return:
    [Workflow|null]

---

### add
##### Description:
    Adds a model to this collection

##### Parameters:
    [object] $model_data Data to feed into attributes of new model
    [array]  $options    Data to make properties of the new model

##### Return:
    [Workflow]  The newly-added model

---

