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
    [Workflow] $this

---

### isFinished
##### Description:
    Detects if the workflow has finished

##### Return:
    [boolean] $is_finished True if worklow has finished

---

### isSuccessful
##### Description:
    Detects if the workflow was successfsul

##### Return:
    [boolean] $is_successful True if workflow succeeded

---

### hasLogs
##### Description:
    Detects if the workflow has any operation with logs

##### Return:
    [boolean] $has_logs True if worklow has logs

---

### operations
##### Description:
    Returns a list of WorkflowOperations for this workflow

##### Return:
    [Array<WorkflowOperation>] $operations list of WorkflowOperations

---

### serialize
##### Description:
    Formats workflow object into an associative array for output

##### Return:
    [array] $data associative array of data for output

---

### wait
##### Description:
    Waits on this workflow to finish

##### Return:
    [Workflow] $this

---

