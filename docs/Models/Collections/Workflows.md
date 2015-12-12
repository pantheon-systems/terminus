# Terminus\Models\Collections\Workflows

### create
##### Description:
    Creates a new workflow and adds its data to the collection

##### Parameters:
    [string] $type    Type of workflow to create
    [array]  $options Additional information for the request
    -[string] environment UUID of environment running workflow
    -[array]  params      Parameters for the request

##### Return:
    [TerminusModel] $model

---

### fetchWithOperations
##### Description:
    Fetches workflow data hydrated with operations

##### Parameters:
    [array] $options Additional information for the request

##### Return:
    [Workflows] $this

---

### fetchWithOperationsAndLogs
##### Description:
    Fetches workflow data hydrated with operations and logs

##### Parameters:
    [array] $options Additional information for the request

##### Return:
    [Workflows] $this

---

### allFinished
##### Description:
    Returns all existing workflows that have finished

##### Return:
    [Array<Workflows>] $workflows

---

### allWithLogs
##### Description:
    Returns all existing workflows that contain logs

##### Return:
    [Array<Workflows>] $workflows

---

### findLatestWithLogs
##### Description:
    Get most-recent workflow from existingcollection that has logs

##### Return:
    [Workflow] $workflow

---

