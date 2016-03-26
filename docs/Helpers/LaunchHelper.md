# Terminus\Helpers\LaunchHelper

### assocArgsToStr
##### Description:
    Composes associative arguments into a command string

##### Parameters:
    [array] $assoc_args Arguments for command line in array form

##### Return:
    [string] Command string form of param

---

### launch
##### Description:
    Launch an external process that takes over I/O.

##### Parameters:
    [array] $arg_options Elements as follow:
    -string command         Command to call
    -array  descriptor_spec How PHP passes descriptor to child process
    -bool   exit_on_error   True to exit if the command returns error

##### Return:
    [int]   The command exit status

---

### launchSelf
##### Description:
    Launch another Terminus command using the runtime arguments for the
    current process

##### Parameters:
    [array] $arg_options Elements as follow:
    -string command       Command to call
    -array  args          Positional arguments to use
    -array  assoc_args    Associative arguments to use
    -bool   exit_on_error True to exit if the command returns error

##### Return:
    [int]   The command exit status

---

