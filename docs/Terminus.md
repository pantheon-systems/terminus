# Using Terminus as a library

Using Terminus as a library is possible as of version 0.10.0. With it, those writing PHP scripts to manage their websites on the Pantheon Platform need no longer shell out in order to use it.

### Basics

Terminus uses models and collections to represent concepts on the Pantheon platform. A few of these can be accessed directly, while others' data is derived from other models and collections. Only those top-level classes may be instantiated directly.

#### Utilities
To operate Terminus as a library, you will need to be authenticated.
- [Terminus\Auth](Auth.md)

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

    $sites        = new Sites();
    $all_sites    = $sites->all();
    $one_site     = array_shift($all_sites);
    $environments = $one_site->environments->all();

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
- [Gleaning all hostnames from all your sites](examples/getHostnames.php)
