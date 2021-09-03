# terminus : Pantheon's Command-Line Interface

## ***COTRIBUTING***

We need help with testing. Take a look in `tests/Functional` and any test that's just `$this->fail("figure out how to test");` and feel free to file a pull request against that.

## Status

[![Commit Build](https://github.com/pantheon-systems/terminus/actions/workflows/ci.yml/badge.svg?branch=v3.0)](https://github.com/pantheon-systems/terminus/actions/workflows/ci.yml)

## About

Terminus is Pantheon's Command Line Interface (CLI), providing at least equivalent functionality to the Pantheon's
browser-based Dashboard and easier scripting.

If you would like to contribute, pull requests are welcome!

## The Manual

Our documentation is kept in the Terminus Manual, located here: https://pantheon.io/docs/terminus

## Requirements

| Operating System       | Version    |
|------------------------|------------|
| MacOS                  | 10.14+     |
| Ubuntu                 | Latest LTR |
| Windows + WSL + Ubuntu | TBD        |

### Package Manager

- [apt](https://ubuntu.com/server/docs/package-management) for Ubuntu/WinWSL-Ubuntu

- [Homebrew](https://brew.sh) for mac

#### Required Packages

These packages will install when you install Terminus.

- [Composer 2](https://getcomposer.org)

- [PHP](https://www.php.net) (v7.4+)

- [Git](https://help.github.com/articles/set-up-git/)

#### Recommended Packages

### Recommended

- [Composer](https://getcomposer.org/doc/00-intro.md) (Needed to use the `self:plugin` commands.)
- [Drush](http://docs.drush.org/en/master/install/) (Useful to run incompatible-with-Terminus Drush commands)
- [WP-CLI](http://wp-cli.org/) (Useful to run incompatible-with-Terminus WP-CLI commands)


## Installation

### Mac OS:

`> brew tap *** TBD ***`

`> brew install *** TBD ***`

### Ubuntu / WinWSL+Ubuntu:

`*** TBD ***`

## Testing

To run the functional tests:

1. If you need the basics (php, git, direnv, etc): `brew bundle install`
2. For code coverage: `pecl install pcov`
3. Prepare a new test site, e.g. make a new multidev in ci-terminus-composer
4. Copy .env.dist to .env and make an .envrc to include it with `dotenv`, or copy .env.dist to .envrc and add `export` at the head of each line. Customize values as needed. Use `direnv allow` to enable automatic loading of environment variables needed for tests.
5. Run `composer test:short` to run the short functional tests, or `composer test:long` to run everything.
 

| Command                   | Description                                                  | ⚖️ |
| ------------------------- | ------------------------------------------------------------ | -- |
| aliases                   | Generates Pantheon Drush aliases for sites on which the      | ❓ |
|                           | currently logged-in user is on the team.                     |    |
| art                       | Displays Pantheon ASCII artwork.                             | ❓ |
| auth:login                | Logs in a user to Pantheon.                                  | ❓ |
| auth:logout               | Logs out the currently logged-in user and deletes any saved  | ❓ |
|                           | machine tokens.                                              |    |
| auth:whoami               | Displays information about the currently logged-in user.     | ❓ |
| backup:automatic:disable  | Disables automatic backups.                                  | ❓ |
| backup:automatic:enable   | Enables automatic daily backups that are retained for one    | ❓ |
|                           | week and weekly backups retained for one month.              |    |
| backup:automatic:info     | Displays the hour when daily backups are created and the day | ❓ |
|                           | of the week when weekly backups are created.                 |    |
| backup:create             | Creates a backup of a specific site and environment.         | ❓ |
| backup:get                | Displays the download URL for a specific backup or latest    | ❓ |
|                           | backup.                                                      |    |
| backup:info               | Displays information about a specific backup or the latest   | ❓ |
|                           | backup.                                                      |    |
| backup:list               | Lists backups for a specific site and environment.           | ❓ |
| backup:restore            | Restores a specific backup or the latest backup.             | ❓ |
| branch:list               | Displays list of git branches for a site.                    | ❓ |
| connection:info           | Displays connection information for Git, SFTP, MySQL, and    | ❓ |
|                           | Redis.                                                       |    |
| connection:set            | Sets Git or SFTP connection mode on a development            | ❓ |
|                           | environment (excludes Test and Live).                        |    |
| d9ify:process             | Clone a pantheon site and spelunk the contents to create new | ❓ |
|                           | D9 site.                                                     |    |
| dashboard:view            | Displays the URL for the Pantheon Dashboard or opens the     | ❓ |
|                           | Dashboard in a browser.                                      |    |
| domain:add                | Associates a domain with the environment.                    | ❓ |
| domain:dns                | Displays recommended DNS settings for the environment.       | ❓ |
| domain:list               | Displays domains associated with the environment.            | ❓ |
| domain:lookup             | Displays site and environment with which a given domain is   | ❓ |
|                           | associated. Note: Only sites for which the user is           |    |
|                           | authorized will appear.                                      |    |
| domain:primary:add        | Sets a domain associated to the environment as primary,      | ❓ |
|                           | causing all traffic to redirect to it.                       |    |
| domain:primary:remove     | Removes the primary designation from the primary domain in   | ❓ |
|                           | the site and environment.                                    |    |
| domain:remove             | Disassociates a domain from the environment.                 | ❓ |
| env:clear-cache           | Clears caches for the environment.                           | ❓ |
| env:clone-content         | Clones database/files from one environment to another        | ❓ |
|                           | environment.                                                 |    |
| env:code-log              | Displays the code log for the environment.                   | ❓ |
| env:commit                | Commits code changes on a development environment. Note: The | ❓ |
|                           | environment connection mode must be set to SFTP.             |    |
| env:deploy                | Deploys code to the Test or Live environment. Notes: -       | ❓ |
|                           | Deploying the Test environment will deploy code from the Dev |    |
|                           | environment. - Deploying the Live environment will deploy    |    |
|                           | code from the Test environment.                              |    |
| env:diffstat              | Displays the diff of uncommitted code changes on a           | ❓ |
|                           | development environment.                                     |    |
| env:info                  | Displays environment status and configuration.               | ❓ |
| env:list                  | Displays a list of the site environments.                    | ❓ |
| env:metrics               | Displays the pages served and unique visit metrics for the   | ❓ |
|                           | specified site environment. The most recent data up to the   |    |
|                           | current day is returned.                                     |    |
| env:view                  | Displays the URL for the environment or opens the            | ❓ |
|                           | environment in a browser.                                    |    |
| env:wake                  | Wakes the environment by pinging it. Note: Development       | ❓ |
|                           | environments and Sandbox sites will automatically sleep      |    |
|                           | after a period of inactivity.                                |    |
| env:wipe                  | Deletes all files and database content in the environment.   | ❓ |
| https:info                | Provides information for HTTPS on being used for the         | ❓ |
|                           | environment.                                                 |    |
| https:remove              | Disables HTTPS and removes the SSL certificate from the      | ❓ |
|                           | environment.                                                 |    |
| https:set                 | Enables HTTPS and/or updates the SSL certificate for the     | ❓ |
|                           | environment.                                                 |    |
| import:complete           | Finalizes the Pantheon import process.                       | ❓ |
| import:database           | Imports a database archive to the environment.               | ❓ |
| import:files              | Imports a file archive to the environment.                   | ❓ |
| import:site               | Imports a site archive (code, database, and files) to the    | ❓ |
|                           | site.                                                        |    |
| local:clone               | CLone a copy of the site code into                           | ❓ |
|                           | $HOME/pantheon-local-copies                                  |    |
| local:commitAndPush       | CLone a copy of site code into $HOME/pantheon-local-copies   | ❓ |
| local:getLiveDB           | Create new backup of your live site db and download to       | ❓ |
|                           | $HOME/pantheon-local-copies/{Site}/db                        |    |
| local:getLiveFiles        | Create new backup of your live site FILES folder and         | ❓ |
|                           | download to $HOME/pantheon-local-copies/{Site}/db            |    |
| lock:disable              | Disables HTTP basic authentication on the environment.       | ❓ |
| lock:enable               | Enables HTTP basic authentication on the environment. Note:  | ❓ |
|                           | HTTP basic authentication username and password are stored   |    |
|                           | in plaintext.                                                |    |
| lock:info                 | Displays HTTP basic authentication status and configuration  | ❓ |
|                           | for the environment.                                         |    |
| machine-token:delete      | Deletes a currently logged-in user machine token.            | ❓ |
| machine-token:delete-all  | Delete all stored machine tokens and log out.                | ❓ |
| machine-token:list        | Lists the currently logged-in user machine tokens.           | ❓ |
| multidev:create           | Creates a multidev environment. If there is an existing Git  | ❓ |
|                           | branch with the multidev name, then it will be used when the |    |
|                           | new environment is created.                                  |    |
| multidev:delete           | Deletes a Multidev environment.                              | ❓ |
| multidev:list             | Lists a site Multidev environments.                          | ❓ |
| multidev:merge-from-dev   | Merges code commits from the Dev environment into a Multidev | ❓ |
|                           | environment.                                                 |    |
| multidev:merge-to-dev     | Merges code commits from a Multidev environment into the Dev | ❓ |
|                           | environment.                                                 |    |
| new-relic:disable         | Disables New Relic for a site.                               | ❓ |
| new-relic:enable          | Enables New Relic for a site.                                | ❓ |
| new-relic:info            | Displays New Relic configuration.                            | ❓ |
| org:list                  | Displays the list of organizations.                          | ❓ |
| org:people:add            | Adds a user to an organization.                              | ❓ |
| org:people:list           | Displays the list of users associated with an organization.  | ❓ |
| org:people:remove         | Removes a user from an organization.                         | ❓ |
| org:people:role           | Changes a user role within an organization.                  | ❓ |
| org:site:list             | Displays the list of sites associated with an organization.  | ❓ |
| org:site:remove           | Removes a site from an organization.                         | ❓ |
| org:upstream:list         | Displays the list of upstreams belonging to an organization. | ❓ |
| owner:set                 | Change the owner of a site                                   | ❓ |
| payment-method:add        | Associates an existing payment method with a site.           | ❓ |
| payment-method:list       | Displays the list of payment methods for the currently       | ❓ |
|                           | logged-in user.                                              |    |
| payment-method:remove     | Disassociates the active payment method from a site.         | ❓ |
| plan:info                 | Displays information about a site plan.                      | ❓ |
| plan:list                 | Displays the list of available site plans.                   | ❓ |
| plan:set                  | Changes a site plan.                                         | ❓ |
| redis:disable             | Disables Redis add-on for a site.                            | ❓ |
| redis:enable              | Enables Redis add-on for a site.                             | ❓ |
| remote:drush              | Runs a Drush command remotely on a site environment.         | ❓ |
| remote:wp                 | Runs a WP-CLI command remotely on a site environment.        | ❓ |
| ssh-key:add               | Associates a SSH public key with the currently logged-in     | ❓ |
|                           | user.                                                        |    |
| ssh-key:list              | Displays the list of SSH public keys associated with the     | ❓ |
|                           | currently logged-in user.                                    |    |
| ssh-key:remove            | Disassociates a SSH public key from the currently logged-in  | ❓ |
|                           | user.                                                        |    |
| self:clear-cache          | Clears the local Terminus command cache.                     | ❓ |
| self:config:dump          | Displays the local Terminus configuration.                   | ❓ |
| self:console              | Opens an interactive PHP console within Terminus. Note: This | ❓ |
|                           | functionality is useful for debugging Terminus or            |    |
|                           | prototyping Terminus plugins.                                |    |
| self:info                 | Displays the local PHP and Terminus environment              | ❓ |
|                           | configuration.                                               |    |
| service-level:set         | Upgrades or downgrades a site service level.                 | ❓ |
| site:create               | Creates a new site.                                          | ❓ |
| site:delete               | Deletes a site from Pantheon.                                | ❓ |
| site:info                 | Displays a site information.                                 | ❓ |
| site:list                 | Displays the list of sites accessible to the currently       | ❓ |
|                           | logged-in user.                                              |    |
| site:lookup               | Displays the UUID of a site given its name.                  | ❓ |
| site:org:add              | Associates a supporting organization with a site.            | ❓ |
| site:org:list             | Displays the list of supporting organizations associated     | ❓ |
|                           | with a site.                                                 |    |
| site:org:remove           | Disassociates a supporting organization from a site.         | ❓ |
| site:team:add             | Adds a user to a site team. Note: An invite will be sent if  | ❓ |
|                           | the email is not associated with a Pantheon account.         |    |
| site:team:list            | Displays the list of team members for a site.                | ❓ |
| site:team:remove          | Removes a user from a site team.                             | ❓ |
| site:team:role            | Updates a user role on a site team.                          | ❓ |
| site:upstream:clear-cache | Clears caches for the site codeserver.                       | ❓ |
| site:upstream:set         | Changes a site upstream.                                     | ❓ |
| solr:disable              | Disables Solr add-on for a site.                             | ❓ |
| solr:enable               | Enables Solr add-on for a site.                              | ❓ |
| tag:add                   | Adds a tag on a site within an organization.                 | ❓ |
| tag:list                  | Displays the list of tags for a site within an organization. | ❓ |
| tag:remove                | Removes a tag from a site within an organization.            | ❓ |
| upstream:info             | Displays information about an upstream.                      | ❓ |
| upstream:list             | Displays the list of upstreams accessible to the currently   | ❓ |
|                           | logged-in user.                                              |    |
| upstream:updates:apply    | Applies upstream updates to a site development environment.  | ❓ |
| upstream:updates:list     | Displays a cached list of new code commits available from    | ❓ |
|                           | the upstream for a site development environment. Note: To    |    |
|                           | refresh the cache you will need to run                       |    |
|                           | site:upstream:clear-cache before running this command.       |    |
| upstream:updates:status   | Displays a whether there are updates available from the      | ❓ |
|                           | upstream for a site environment.                             |    |
| workflow:info:logs        | Displays the details of a workflow including Quicksilver     | ❓ |
|                           | operation logs.                                              |    |
| workflow:info:operations  | Displays Quicksilver operation details of a workflow.        | ❓ |
| workflow:info:status      | Displays the status of a workflow.                           | ❓ |
| workflow:list             | Displays the list of the workflows for a site.               | ❓ |
| workflow:watch            | Streams new and finished workflows from a site to the        | ❓ |
|                           | console.                                                     |    |
| ------------------------- | ------------------------------------------------------------ | -- |

Testing Legend: ✅ Pass     💩 Bad test     🤮 Exception     ❌ Fail️️     ⚠️ Warning     ❓ Missing/Not Written

Tests Passing:  0 / 118 ( 118 not written / missing )
