# Terminus\Models\Environment

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

---

### addHostname
##### Description:
    Add hostname to environment

##### Parameters:
    [string] $hostname Hostname to add to environment

##### Return:
    [array] Response data

---

### changeConnectionMode
##### Description:
    Changes connection mode

##### Parameters:
    [string] $value Connection mode, "git" or "sftp"

##### Return:
    [Workflow|string]

---

### cloneDatabase
##### Description:
    Clones database from this environment to another

##### Parameters:
    [string] $to_env Environment to clone into

##### Return:
    [Workflow]

---

### cloneFiles
##### Description:
    Clones files from this environment to another

##### Parameters:
    [string] $to_env Environment to clone into

##### Return:
    [Workflow]

---

### commitChanges
##### Description:
    Commits changes to code

##### Parameters:
    [string] $commit Should be the commit message to use if committing
    -on server changes

##### Return:
    [array] Response data

---

### connectionInfo
##### Description:
    Gives connection info for this environment

##### Return:
    [array]

---

### create
##### Description:
    Creates a new environment

##### Parameters:
    [string] $env_name Name of environment to create

##### Return:
    [array] Response data

---

### deleteHostname
##### Description:
    Delete hostname from environment

##### Parameters:
    [string] $hostname Hostname to remove from environment

##### Return:
    [array] Response data

---

### deploy
##### Description:
    Deploys the Test or Live environment

##### Parameters:
    [array] $params Parameters for the deploy workflow

##### Return:
    [Workflow]

---

### diffstat
##### Description:
    Gets diff from multidev environment

##### Return:
    [array]

---

### domain
##### Description:
    Generate environment URL

##### Return:
    [string]

---

### getConnectionMode
##### Description:
    Returns the connection mode of this environment

##### Return:
    [string] 'git' or 'sftp'

---

### getHostnames
##### Description:
    List hostnames for environment

##### Return:
    [array]

---

### getName
##### Description:
    Returns the environment's name

##### Return:
    [string]

---

### info
##### Description:
    Load site info

##### Parameters:
    [string] $key Set to retrieve a specific attribute as named

##### Return:
    [array] $info

---

### initializeBindings
##### Description:
    Initializes the test/live environments on a newly created site  and clones
    content from previous environment (e.g. test clones dev content, live
    clones test content.)

##### Return:
    [Workflow] In-progress workflow

---

### isInitialized
##### Description:
    Have the environment's bindings have been initialized?

##### Return:
    [bool] True if environment has been instantiated

---

### isMultidev
##### Description:
    Is this branch a multidev environment?

##### Return:
    [bool] True if ths environment is a multidev environment

---

### lock
##### Description:
    Enable HTTP Basic Access authentication on the web environment

##### Parameters:
    [array] $options Parameters to override defaults

##### Return:
    [Workflow]

---

### lockinfo
##### Description:
    Get Info on an environment lock

##### Return:
    [string]

---

### log
##### Description:
    Get the code log (commits)

##### Return:
    [array]

---

### mergeFromDev
##### Description:
    Merge code from the Dev Environment into this Multidev Environment

##### Parameters:
    [array] $options Parameters to override defaults

##### Return:
    [Workflow]

---

### mergeToDev
##### Description:
    Merge code from this Multidev Environment into the Dev Environment

##### Parameters:
    [array] $options Parameters to override defaults

##### Return:
    [Workflow]

---

### unlock
##### Description:
    Disable HTTP Basic Access authentication on the web environment

##### Return:
    [Workflow]

---

### wake
##### Description:
    "Wake" a site

##### Return:
    [array]

---

### wipe
##### Description:
    Deletes all content (files and database) from the Environment

##### Return:
    [Workflow]

---

### workflow
##### Description:
    Start a work flow

##### Parameters:
    [Workflow] $workflow String work flow "slot"

##### Return:
    [array]

---

