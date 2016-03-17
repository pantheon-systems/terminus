# Terminus\Models\Auth

### __construct
##### Description:
    Object constructor

##### Parameters:
    [object] $attributes Attributes of this model
    [array]  $options    Options to set as $this->key

##### Return:
    [Auth]

---

### getAllSavedTokenEmails
##### Description:
    Gets all email addresses for which there are saved machine tokens

##### Return:
    [string[]]

---

### getMachineTokenCreationUrl
##### Description:
    Generates the URL string for where to create a machine token

##### Return:
    [string]

---

### loggedIn
##### Description:
    Checks to see if the current user is logged in

##### Return:
    [bool] True if the user is logged in

---

### logInViaMachineToken
##### Description:
    Execute the login based on a machine token

##### Parameters:
    [string[]] $args Elements as follow:
    -string token Machine token to initiate login with
    -string email Email address to locate token with

##### Return:
    [bool] True if login succeeded

##### Throws:
    TerminusException

---

### logInViaUsernameAndPassword
##### Description:
    Execute the login via email/password

##### Parameters:
    [string] $email    Email address associated with a Pantheon account
    [string] $password Password for the account

##### Return:
    [bool] True if login succeeded

##### Throws:
    TerminusException

---

### tokenExistsForEmail
##### Description:
    Checks to see whether the email has been set with a machine token

##### Parameters:
    [string] $email Email address to check for

##### Return:
    [bool]

---

