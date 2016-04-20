# Using Terminus as a library

Using Terminus as a library is possible as of version 0.10.0. With it, those writing PHP scripts to manage their websites on the Pantheon Platform need no longer shell out in order to use it.

### Basics

Terminus uses models and collections to represent concepts on the Pantheon platform. A few of these can be accessed directly, while others' data is derived from other models and collections. Only those top-level classes may be instantiated directly.

#### Utilities
To operate Terminus as a library, you will need to be authenticated.
- [Terminus\Auth](Models/Auth.md)

#### Abstract Parent Classes
Although they cannot be accessed directly, all other models and collections can use the commands herein.
- [Terminus\Models\TerminusModel](Models/TerminusModel.md)
- [Terminus\Models\Collections\TerminusCollection](Models/Collections/TerminusCollection.md)

#### Top-level Models & Collections
It is possible to create new instances of each of these.

    $sites = new Sites();

- [Terminus\Models\User](Models/User.md)
- [Terminus\Models\Collections\Sites](Models/Collections/Sites.md)
- [Terminus\Models\Collections\Upstreams](Models/Collections/Upstreams.md)
- [Terminus\Models\Collections\Workflows](Models/Collections/Workflows.md)

#### Derivative Models & Collections
You can access these via their owners.
```bash
    $sites        = new Sites();
    $all_sites    = $sites->all();
    $one_site     = array_shift($all_sites);
    $environments = $one_site->environments->all();
```
- [Terminus\Models\Backup](Models/Backup.md)
- [Terminus\Models\Environment](Models/Environment.md)
- [Terminus\Models\Organization](Models/Organization.md)
- [Terminus\Models\OrganizationSiteMembership](Models/OrganizationSiteMembership.md)
- [Terminus\Models\OrganizationUserMembership](Models/OrganizationUserMembership.md)
- [Terminus\Models\Site](Models/Site.md)
- [Terminus\Models\SiteOrganizationMembership](Models/SiteOrganizationMembership.md)
- [Terminus\Models\SiteUserMembership](Models/SiteUserMembership.md)
- [Terminus\Models\Workflow](Models/Workflow.md)
- [Terminus\Models\WorkflowOperation](Models/WorkflowOperation.md)
- [Terminus\Models\Collections\Backups](Models/Collections/Backups.md)
- [Terminus\Models\Collections\Bindings](Models/Collections/Bindings.md)
- [Terminus\Models\Collections\Environments](Models/Collections/Environments.md)
- [Terminus\Models\Collections\OrganizationSiteMemberships](Models/Collections/OrganizationSiteMemberships.md)
- [Terminus\Models\Collections\OrganizationUserMemberships](Models/Collections/OrganizationUserMemberships.md)
- [Terminus\Models\Collections\SiteOrganizationMemberships](Models/Collections/SiteOrganizationMemberships.md)
- [Terminus\Models\Collections\SiteUserMemberships](Models/Collections/SiteUserMemberships.md)
- [Terminus\Models\Collections\UserOrganizationMemberships](Models/Collections/UserOrganizationMemberships.md)

### Examples
- [Gleaning all the hostnames from all of your sites](examples/getHostnames.php)
- [Generating Drush aliases for all your sites](examples/PantheonAliases.php)

### Getting Started

1. Install Terminus via [Composer](https://getcomposer.org/download/).
Navigate to the directory in which your script is being constructed in the
terminal and use this command:

    `composer require pantheon-systems/terminus`

2. Include the Terminus source code. Composer handily places the code you
called for into the `vendor` directory within the directory you are in.
Use this to load Terminus' source to your script:

    `require vendor/autoload.php`

3. Use the namespaces of the top-level models or collections you are going to make use of

    `use Terminus\Models\Collections\Sites;`

4. Instantiate the top-level models or collections you are using.

    `$sites = new Sites();`

5. Use its properties and functions.

    ```bash
    $my_site         = $sites->get('my_site');
    $dev_environment = $my_site->environments->get('dev'); 
    $connection_info = $dev_environment->connectionInfo();
    ```

or

`$sites->addSite(array('label' => 'My Site', 'site_name' => 'my_site'));`

### Tips

- You can configure how your Terminus object will behave upon creation. Here
are the options you can set when instantiating it:
  - `colorize`: Output text to be colorized as indicated by the functions
  using it. Defaults to 'auto'.
  - `format`: 'normal', 'json', or 'bash'. Defaults to 'json'. Formats in
  Terminus are mostly related to how command-line execution results are
  displayed, but will affect how the STDERR logs are transmitted, as well.
  - `debug`: Output debug logs. Defaults to false.
  You can set these like such in step 3 of the getting-started section above:
```bash
$terminus = new Terminus(
  array('colorize' => false, 'format' => 'bash', 'debug' => true)
);
```
- Many functions will return a Workflow-type object. These functions require
time to complete their operations on the Pantheon platform. To wait for their
completion, use the `wait()` function.
```bash
$workflow = $object->workflowReturningFunction();
$workflow->wait();
$object->otherObjectFunction();
```
