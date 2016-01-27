# Terminus\Models\Workflow

### getFetchUrl
##### Description:
    Give the URL for collection data fetching

##### Return:
    [string] $url URL to use in fetch query

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

