# Terminus\Models\Collections\SshKeys

### addKey
##### Description:
    Adds an SSH key to the user's Pantheon account

##### Parameters:
    [string] $key_file Full path of the SSH key to add

##### Return:
    [array]

##### Throws:
    TerminusException

---

### deleteAll
##### Description:
    Deletes all SSH keys from account

##### Return:
    [array]

---

### fetch
##### Description:
    Fetches model data from API and instantiates its model instances

##### Parameters:
    [array] $options params to pass to url request

##### Return:
    [SshKeys] $this

---

