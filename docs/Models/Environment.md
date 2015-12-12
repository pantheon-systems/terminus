# Terminus\Models\Environment

### __construct
##### Description:
    Object constructor

##### Parameters:
    [stdClass] $attributes Attributes of this model
    [array]    $options    Options to set as $this->key

##### Return:
    [TerminusModel] $this

---

### addHostname
##### Description:
    Add hostname to environment

##### Parameters:
    [string] $hostname Hostname to add to environment

##### Return:
    [array] $response['data']

---

### changeConnectionMode
##### Description:
    Changes connection mode

##### Parameters:
    [string] $value Connection mode, "git" or "sftp"

##### Return:
    [Workflow] $workflow

---

### cloneDatabase
##### Description:
    Clones files from this environment to another

##### Parameters:
    [string] $to_env Environment to clone into

##### Return:
    [Workflow] $workflow

---

### cloneFiles
##### Description:
    Clones files from this environment to another

##### Parameters:
    [string] $to_env Environment to clone into

##### Return:
    [Workflow] $workflow

---

### commitChanges
##### Description:
    Commits changes to code

##### Parameters:
    [string] $commit Should be the commit message to use if committing
    -on server changes

##### Return:
    [array] $data['data']

---

### connectionInfo
##### Description:
    Gives connection info for this environment

##### Return:
    [array] $info

---

### create
##### Description:
    Creates a new environment

##### Parameters:
    [string] $env_name Name of environment to create

##### Return:
    [array] $response['data']

---

### deleteHostname
##### Description:
    Delete hostname from environment

##### Parameters:
    [string] $hostname Hostname to remove from environment

##### Return:
    [array] $response['data']

---

### deploy
##### Description:
    Deploys the Test or Live environment

##### Parameters:
    [array] $params Parameters for the deploy workflow

##### Return:
    [Workflow] workflow response

---

### diffstat
##### Description:
    Gets diff from multidev environment

##### Return:
    [array] $data['data']

---

### domain
##### Description:
    Generate environment URL

##### Return:
    [string] $host

---

### getConnectionMode
##### Description:
    Returns the connection mode of this environment

##### Return:
    [string] $connection_mode

---

### getHostnames
##### Description:
    List hotnames for environment

##### Return:
    [array] $response['data']

---

### getName
##### Description:
    Returns the environment's name

##### Return:
    [string] $name

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
    [Workflow] $workflow In-progress workflow

---

### isInitialized
##### Description:
    Have the environment's bindings have been initialized?

##### Return:
    [boolean] $has_commits True if environment has been instantiated

---

### isMultidev
##### Description:
    Is this branch a multidev environment?

##### Return:
    [boolean] True if ths environment is a multidev environment

---

### lock
##### Description:
    Enable HTTP Basic Access authentication on the web environment

##### Parameters:
    [array] $options Parameters to override defaults

##### Return:
    [Workflow] $workflow;

---

### lockinfo
##### Description:
    Get Info on an environment lock

##### Return:
    [string] $lock

---

### log
##### Description:
    Get the code log (commits)

##### Return:
    [array] $response['data']

---

### mergeFromDev
##### Description:
    Merge code from the Dev Environment into this Multidev Environment

##### Parameters:
    [array] $options Parameters to override defaults

##### Return:
    [Workflow] $workflow

---

### mergeToDev
##### Description:
    Merge code from this Multidev Environment into the Dev Environment

##### Parameters:
    [array] $options Parameters to override defaults

##### Return:
    [Workflow] $workflow

---

### unlock
##### Description:
    Disable HTTP Basic Access authentication on the web environment

##### Return:
    [Workflow] $workflow

---

### wake
##### Description:
    "Wake" a site

##### Return:
    [array] $return_data

---

### wipe
##### Description:
    Deletes all content (files and database) from the Environment

##### Return:
    [Workflow] $workflow

---

### workflow
##### Description:
    Start a work flow

##### Parameters:
    [Workflow] $workflow String work flow "slot"

##### Return:
    [array] $response['data']

---

