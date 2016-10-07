# Terminus\Models\Environment

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options with which to configure this model

---

### applyUpstreamUpdates
##### Description:
    Apply upstream updates

##### Parameters:
    [boolean] $updatedb True to run update.php
    [boolean] $xoption  True to automatically resolve merge conflicts

##### Return:
    [Workflow]

---

### cacheserverConnectionInfo
##### Description:
    Gives cacheserver connection info for this environment

##### Return:
    [array]

---

### changeConnectionMode
##### Description:
    Changes connection mode

##### Parameters:
    [string] $value Connection mode, "git" or "sftp"

##### Return:
    [Workflow|string]

---

### clearCache
##### Description:
    Clears an environment's cache

##### Return:
    [Workflow]

---

### cloneDatabase
##### Description:
    Clones database from this environment to another

##### Parameters:
    [string] $from_env Name of the environment to clone

##### Return:
    [Workflow]

---

### cloneFiles
##### Description:
    Clones files from this environment to another

##### Parameters:
    [string] $from_env Name of the environment to clone

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

### convergeBindings
##### Description:
    Converges all bindings on a site

##### Return:
    [array]

---

### countDeployableCommits
##### Description:
    Counts the number of deployable commits

##### Return:
    [int]

---

### databaseConnectionInfo
##### Description:
    Gives database connection info for this environment

##### Return:
    [array]

---

### delete
##### Description:
    Delete a multidev environment

##### Parameters:
    [array] $arg_options Elements as follow:
    -bool delete_branch True to delete branch

##### Return:
    [Workflow]

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

### getDrushVersion
##### Description:
    Gets the Drush version of this environment

##### Return:
    [int]

---

### getName
##### Description:
    Returns the environment's name

##### Return:
    [string]

---

### getParentEnvironment
##### Description:
    Returns the parent environment

##### Return:
    [Environment]

---

### gitConnectionInfo
##### Description:
    Gives Git connection info for this environment

##### Return:
    [array]

---

### hasDeployableCode
##### Description:
    Decides if the environment has changes to deploy

##### Return:
    [bool]

---

### importDatabase
##### Description:
    Imports a database archive

##### Parameters:
    [string] $url URL to import data from

##### Return:
    [Workflow]

---

### import
##### Description:
    Imports a site archive onto Pantheon

##### Parameters:
    [string] $url URL of the archive to import

##### Return:
    [Workflow]

---

### importFiles
##### Description:
    Imports a file archive

##### Parameters:
    [string] $url URL to import data from

##### Return:
    [Workflow]

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
    [array] $params Elements as follow:
    -string username
    -string password

##### Return:
    [Workflow]

---

### lockinfo
##### Description:
    Get Info on an environment lock

##### Return:
    [string]

---

### mergeFromDev
##### Description:
    Merge code from the Dev Environment into this Multidev Environment

##### Parameters:
    [array] $options Parameters to override defaults
    -boolean updatedb True to update DB with merge

##### Return:
    [Workflow]

##### Throws:
    TerminusException

---

### mergeToDev
##### Description:
    Merge code from a multidev environment into the dev environment

##### Parameters:
    [array] $options Parameters to override defaults
    -string  from_environment Name of the multidev environment to merge
    -boolean updatedb         True to update DB with merge

##### Return:
    [Workflow]

##### Throws:
    TerminusException

---

### sendCommandViaSsh
##### Description:
    Sends a command to an environment via SSH.

##### Parameters:
    [string] $command The command to be run on the platform

##### Return:
    [string[]] $response Elements as follow:
    -string output    The output from the command run
    -string exit_code The status code returned by the command run

---

### serialize
##### Description:
    Formats environment object into an associative array for output

##### Return:
    [array] Associative array of data for output

---

### setHttpsCertificate
##### Description:
    Add/replace an HTTPS certificate on the environment

##### Parameters:
    [array] $certificate Certificate data elements as follow
    -string cert         Certificate
    -string key          RSA private key
    -string intermediary CA intermediate certificate(s)

##### Return:
    [$workflow]

---

### sftpConnectionInfo
##### Description:
    Gives SFTP connection info for this environment

##### Return:
    [array]

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

