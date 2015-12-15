# Terminus\Auth

### __construct
##### Description:
    Object constructor. Sets the logger class property.

---

### ensureLogin
##### Description:
    Ensures the user is logged in or errs.

##### Return:
    [bool] Always true

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
    [string] $token Machine token to initiate login with

##### Return:
    [bool] True if login succeeded

---

### logInViaSessionToken
##### Description:
    Execute the login based on an existing session token

##### Parameters:
    [string] $token Session token to initiate login with

##### Return:
    [bool] True if login succeeded

---

### logInViaUsernameAndPassword
##### Description:
    Execute the login via email/password

##### Parameters:
    [string] $email    Email address associated with a Pantheon account
    [string] $password Password for the account

##### Return:
    [bool] True if login succeeded

---

