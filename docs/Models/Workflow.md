# Terminus\Models\Workflow

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options with which to configure this model

##### Return:
    [Workflow]

---

### fetchWithLogs
##### Description:
    Re-fetches workflow data hydrated with logs

##### Return:
    [Workflow]

---

### getStatus
##### Description:
    Returns the status of this workflow

##### Return:
    [string]

---

### isFinished
##### Description:
    Detects if the workflow has finished

##### Return:
    [bool] True if workflow has finished

---

### isSuccessful
##### Description:
    Detects if the workflow was successful

##### Return:
    [bool] True if workflow succeeded

---

### operations
##### Description:
    Returns a list of WorkflowOperations for this workflow

##### Return:
    [WorkflowOperation[]]

---

### serialize
##### Description:
    Formats workflow object into an associative array for output

##### Return:
    [array] Associative array of data for output

---

### wait
##### Description:
    Waits on this workflow to finish

##### Return:
    [Workflow|void]

##### Throws:
    TerminusException

---

### checkProgress
##### Description:
    Check on the progress of a workflow. This can be called repeatedly and will apply a polling
    period to prevent flooding the API with requests.

##### Return:
    [bool] Whether the workflow is finished or not

##### Throws:
    \Terminus\Exceptions\TerminusException

---

### getMessage
##### Description:
    Get the success message of a workflow or throw an exception of the workflow failed.

##### Return:
    [string] The message to output to the user

##### Throws:
    \Terminus\Exceptions\TerminusException

---

