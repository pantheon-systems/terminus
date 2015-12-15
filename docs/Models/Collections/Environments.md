# Terminus\Models\Collections\Environments

### create
##### Description:
    Creates a multidev environment

##### Parameters:
    [string]      $to_env_id Name of new the environment
    [Environment] $from_env  Environment to clone from

##### Return:
    [Workflow]

---

### ids
##### Description:
    List Environment IDs, with Dev/Test/Live first

##### Return:
    [string[]] $ids

---

### multidev
##### Description:
    Returns a list of all multidev environments on the collection-owning Site

##### Return:
    [Environment[]]

---

