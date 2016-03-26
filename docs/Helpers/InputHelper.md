# Terminus\Helpers\InputHelper

### backup
##### Description:
    Produces a menu to select a backup

##### Parameters:
    [array] $arg_options Elements as follow:
    -string label   Prompt for STDOUT
    -array  backups Array of Backup objects

##### Return:
    [\stdClass] An object representing the backup desired

##### Throws:
    TerminusException

---

### backupElement
##### Description:
    Produces a menu to narrow down an element selection

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    Arguments given via param
    -string key     Args key to search for
    -string label   Prompt for STDOUT
    -array  choices Menu options for the user

##### Return:
    [string] Either the selection, its index, or the default

##### Throws:
    TerminusException

---

### day
##### Description:
    Facilitates the selection of a day of the week

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    Arguments given via param
    -string key     Args key to search for
    -string label   Prompt for STDOUT
    -array  choices Menu options for the user, may be a collection

##### Return:
    [int]

---

### env
##### Description:
    Produces a menu with the given attributes

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    Arguments given via param
    -string key     Args key to search for
    -string label   Prompt for STDOUT
    -array  choices Menu options for the user, may be a collection
    -Site   site    Site object to gather environment choices from

##### Return:
    [string] Either the selection, its index, or the default

---

### getNullInputs
##### Description:
    Returns the null input list

##### Return:
    [string[]]

---

### fileName
##### Description:
    Produces a menu with the given attributes

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args            Arguments given via param
    -bool   autoselect_solo Automatically selects the only given option
    -array  dir             Directory from which to select options
    -string key             Args key to search for
    -string message         Prompt printed to STDOUT
    -string regex           Regex to match file names to

##### Return:
    [string] The name of the selected file

##### Throws:
    TerminusException

---

### menu
##### Description:
    Produces a menu with the given attributes

##### Parameters:
    [array] $arg_options Elements as follow:
    -bool   autoselect_solo Automatically selects the only given option
    -array  choices         Menu options for the user
    -mixed  default         Given as null option in the menu
    -string message         Prompt printed to STDOUT
    -bool   return_value    If true, returns selection. False, the index

##### Return:
    [string] Either the selection, its index, or the default

---

### optional
##### Description:
    Returns $args[$key] if exists, $default otherwise

##### Parameters:
    [array] $arg_options Elements as follow:
    -string key     Index of arg to return
    -array  choices    Args to search for key
    -mixed  default Returned if $args[$key] DNE

##### Return:
    [mixed] Either $args[$key] or $default

---

### orgId
##### Description:
    Input helper that provides interactive menu to select org name

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args       The args passed in from argv
    -string key        Args key to search for
    -string default    Returned if arg and stdin fail in interactive
    -array  allow_none True to permit no selection to be an option

##### Return:
    [string] ID of selected organization

##### Throws:
    TerminusException

---

### orgMember
##### Description:
    Input helper to aid in selecting an organizational member

##### Parameters:
    [array] $arg_options Elements as follow:
    -array        args            The args passed in from argv
    -bool         autoselect_solo Automatically selects the only member
    -bool         can_pick_self   True allows self-selection if using org
    -array        choices         Args to search for key
    -string       key             Args key to search for
    -string       message         Prompt to STDOUT
    -Organization org             Org to generate choices from

##### Return:
    [string|OrganizationUserMembership]

##### Throws:
    TerminusException

---

### orgName
##### Description:
    Input helper that provides interactive menu to select org name

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args The args passed in from argv
    -string key  Args key to search for

##### Return:
    [string] Site name

---

### orgRole
##### Description:
    Helper function to get an organizational team role

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    Argument array passed from commands
    -string key     Key to look for in args
    -string message Prompt to STDOUT

##### Return:
    [string] Name of role

---

### phpVersion
##### Description:
    Helps select a PHP version for sites and environments

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    Parameters from the command line
    -array  choices Choices to override the built-in ones
    -string key     Key to look for in args
    -string message Prompt to STDOUT

##### Return:
    [string] PHP version

---

### prompt
##### Description:
    Prompt the user for input

##### Parameters:
    [array] $arg_options Elements as follow:
    -string message Message to give at prompt
    -mixed  default Returned if user does not select a valid option

##### Return:
    [string]

##### Throws:
    TerminusException

---

### promptSecret
##### Description:
    Gets input from STDIN silently
    By: Troels Knak-Nielsen
    From: http://www.sitepoint.com/interactive-cli-password-prompt-in-php/

##### Parameters:
    [array] $arg_options Elements as follow:
    -string message Message to give at prompt
    -mixed  default Returned if user does not select a valid option

##### Return:
    [string]

##### Throws:
    TerminusException

---

### siteRole
##### Description:
    Helper function to get site team role

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args Argument array passed from commands
    -string message    Prompt to STDOUT

##### Return:
    [string] Name of role

---

### serviceLevel
##### Description:
    Helper function to select a service level

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    The args passed in from argv
    -string key     Args key to search for
    -string message    Prompt to STDOUT

##### Return:
    [string]

---

### siteName
##### Description:
    Input helper that provides interactive site list

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args    The args passed in from argv
    -string key     Args key to search for
    -string message Prompt for STDOUT

##### Return:
    [string] Site name

---

### string
##### Description:
    Returns $args[key] if exists, then STDIN, then $default

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args     Args already input
    -mixed  default  Returns if no other choice
    -string key      Key for searched-for argument
    -string message  Prompt printed to STDOUT
    -bool   required True to disallow empty strings

##### Return:
    [string] Either $args[$key], $default, or string from prompt

---

### upstream
##### Description:
    Helper function to select valid upstream

##### Parameters:
    [array] $arg_options Elements as follow:
    -array  args Args to parse value from
    -string key  Index to search for in args
    -bool   exit If true, throw error when no value is found

##### Return:
    [string]

##### Throws:
    TerminusException

---

### workflow
##### Description:
    Helper function to select Site Workflow

##### Parameters:
    [array] $arg_options Elements as follow:
    -Workflow[] workflows Array of workflows to list
    -array      args      Args to parse value from
    -string     key       Index to search for in args

##### Return:
    [Workflow]

##### Throws:
    TerminusException

---

