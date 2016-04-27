#Change Log
All notable changes to this project starting with the 0.6.0 release will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org)

## MASTER
### Added
- New field `frozen` appears in `sites list` when a site belonging to your user has been frozen. (#1015)
- New command `site lookup` to look up sites by name. (#1027)

### Changed
- Removed the port number from the create-a-machine token URL seen when using `auth login` except for when the host is localhost. (#1034)
- Changed the `SynopsisValidator` so that any unnamed command argument between `<` and `>` may be given any name. (#1041)
- When running `site deploy` and there are no changes to deploy, Terminus will now exit with status `0` rather than `1`. (#1054)

### Fixed
- `wp` and `drush` commands both now use the object buffer and output the result of the operation without formatting. (#1023)
- `Runner::getLogger()` will no longer return `null`. (#1049)
- Fixed undefined-variable bug when attempting to access list of upstreams in `InputHelper::upstream()`. (#1050)
- Fixed undefined-variable bug which appears when a user has one machine token saved but is logged out. (#1053)
- Multiline option details will now fully appear in help output. (#1055)

## [0.11.1] - 2016-03-30

### Added
- New command `site drush-version` to check the Drush version number of any or all environments. (#1001)
- New command `site set-drush-version` to set the Drush version number of any or all environments. (#1001)
- New parameter `--owner` added to `sites list` to filter the list just for the sites the current user owns. (#1003)
- New option to filter for organization sites via `sites list --org=all`. (#1003)

### Changed
- `InputHelper#upstream()` now returns the UUID of the chosen upstream rather than an upstream object. (#1013)
- `InputHelper#upstream()` will not check upstream data if it has been given a UUID in the $args[$key]. (#1013)
- `InputHelper#orgId()` will not check organizational data if it has been given a UUID in the $args[$key]. (#1013)
- Running `Sites#addSiteToCache` while the cache is empty will no longer trigger a full cache sync. (#1013)

### Fixed
- Alternate command suggestion for `drush "sql-connect"` corrected to `site connection-info --field=mysql_command`. (#1005)
- Fixed output in JSON format for `site hostnames list`. (#1011)

## [0.11.0] - 2016-03-16
### Added
- New command `ssh-keys list` added. (#990)
- New command `ssh-keys add` added. (#990)
- New command `ssh-keys delete` added. (#990)
- New parameter `--env=<env>` has been added to `site import-content`. (#994)

### Changed
- WP-CLI function `import` has been removed from the command blacklist. [See the documentation for more information.](https://github.com/pantheon-systems/documentation/blob/master/source/docs/guides/create-a-wordpress-site-from-the-commandline-with-terminus-and-wp-cli.md) (#979)
- When running `terminus auth login` and more than one machine token is present, Terminus will now tell you how to use them instead of giving the same error message received when no tokens are present. (#987)
- `SiteCommand#import` has been renamed to `SiteCommand#importContent`. (#994)
- `Site#importDatabase` has been moved to `Environment#importDatabase`. (#994)
- `Site#importFiles` has been moved to `Environment#importFiles`. (#994)

### Fixed
- Fixed unidentified index email warning which appeared when logging in via saved machine token by email. (#983)
- Prevented long loop of configurator loadings. (#988)
- Fixed auth status check before running `CommandWithSSH` descendant commands (`drush`, `wp`). (#986)
- Ensured that API and server errors exit with non-zero status. (#996)
- Fixed error in `site import-content` documentation regarding what is importable. (#994)

## [0.10.6] - 2016-03-07
### Changed
- `Terminus\Helpers\AuthHelper` has become `Terminus\Models\Auth`. (#971)

### Fixed
- Now compatible with PHP 7.0. (#973)
- If there no updates to apply, `site upstream-updates apply` will exit stating that there aren't any. (#974)

## [0.10.5] - 2016-02-26
### Added
- Added `site set-php-version` command to set the PHP version on both sites and their environments. (#937)

### Changed
- Data returned from `site code branches` has changed to use hash keys. (#940)
- `workflows show` parameter has changed from `--worklow_id` to `--workflow-id`. (#947)
- Added workflow times to `workflows watch` output. (#951)
- Added workflow times to `workflows watch` output. (#952)
- `site set-https-certificate` parameter has changed from `--private_key` to `--private-key`. (#954)
- `site set-https-certificate` parameter has changed from `--intermediate_certificate` to `--intermediate-certificate`. (#954)
- Changed `InputHelper#string()` so that entries may be required to not be blank. (#954)

### Fixed
- `site code diffstat` no longer gives a non-object error. (#939)
- `InputHelper#env` now will generate a list of all environments when fed a site object. (#950)
- Fixed name and label entry for `sites create` such that they cannot be blank. (#954)

## [0.10.4] - 2016-02-17
### Added
- Added new `role` data to the information returned by `site team list`. (#924)
- Added `site hostnames get-recommendations` to retrieve DNS recommendations. (#933)

### Changed
- The `--set` argument in `site set-owner` has been renamed to `--member` for consistency. (#924)
- Site and environment PHP versions are now reported with the '.' (e.g. 5.3, 5.5). (#931)

### Fixed
- Help text when inputting an incomplete command has been repaired. (#919)
- Automatic checking for the current version will no longer err unless GitHub is down. (#925)
- Fixed strict standards issue occurring in `Environment#wake()`. (#928)
- `Utils\checkForUpdate()` now appropriately interpolates error messages. (#929)
- Changed `Symfony\Finder` versions so as to not conflict with Drupal Console. (#932)
- Ensured that PHP version numbers appear when the API returns no value. (#931)

## [0.10.3] - 2016-02-12
### Added
- New `Hostnames` collection and `Hostname` model. (#860)
- Added 3rd Party plugin support (#857)
- Output destinations can now be set via `Terminus#setOutputter` or an element of the options fed into the `Terminus` constructor. (#873)
- HTTPS Certificates on Site Environments can be added/updated using `site set-https-certificate` (#792)
- New command `organizations team add-member`. (#904)
- New command `organizations team remove-member`. (#904)
- New command `organizations team change-role`. (#904)
- Added new `role` data to the information returned by `organizations team list`. (#904)

### Changed
- When an object cannot be found by `TerminusModel#get`, it now throws an exception rather than issuing a notice. (#861)
- Input helper functions are no longer static. (#864)
- `site set-instrument` no longer does client-side checking for personal instrument access. (#872)
- The `--point=<int>` argument in `cli completions` is now optional. (#873)
- `Outputter#line()` now outputs to the set writer output destination rather than STDERR. (#873)
- `Terminus\Auth` now redesignated as `Terminus\Helpers\AuthHelper`. (#881)
- Helpers are now added to the `TerminusCommand`'s `helpers` property. (#881)
- Extracted launching functions from `Terminus` (base class) and moved them into new `LaunchHelper` class. (#882)
- Extracted template function from `Utils` and moved it into new `TemplateHelper` class. (#884)
- Changed the site-DNE message to instruct the user to run `sites list`. (#895)
- `site set-service-level` now also accepts levels of "professional", "sandbox", and "personal". (#894)
- `organizations team` has been changed to `organizations team list`. (#904)
- Changed log message on `site deploy` to not give the workflow's status but a failure message upon failure. (#896)

### Removed
- `addHostnames`, `deleteHostnames()`, and `getHostnames()` has been removed from `Environment`. Use new hostnames property (contains Hostnames collection) instead. (#860)

### Fixed
- `auth login <email> --password=<password>` will not automatically attempt to log in via machine token if a token with the given email is present. (#865)
- `site deploy` now correctly counts queued commits and will not report there is nothing to deploy erroneously. (#879)
- `site set-service-level` now uses a workflow which will also generate an email upon level change. (#897)
- Commands making use of `InputHelper#string` no longer experience an inaccessable-property error. (#900)
- Fixed `site delete-env` environment selection when there are one or fewer multidev environments present on as site. (#909)
- `site delete-branch` now lists all branches, not just those associated with multidev environments. (#911)
- `site connection-info` no longer errors on some sites which have Redis cache servers. (#912)

## 2016-02-08 - Change repository name
- Renamed repository from `cli` to `terminus`. Renamed the precursor to this project `terminus-deprecated`. These changes may require updating scripts if you are manually fetching packages from GitHub. (#877)

## [0.10.2] - 2016-01-27
### Added
- Added a [Drush alias-generating script](docs/examples/PantheonAliases.php) to the Terminus-as-a-library docmentation examples. (#808)
- New command `site redis enable` to enable Redis caching. (#813)
- New command `site redis disable` to disable Redis caching. (#813)
- New command `site solr enable` to enable Solr indexing. (#814)
- New command `site solr disable` to disable Solr indexing. (#814)
- Added `Environment#getParentEnvironment()`. (#831)
- Added `Commits` collection and `Commit` model. (#831)
- Added `machine-token list` and `machine-token delete` commands. (#798)

### Changed
- `drush` and `wp` commands now issue a warning to change your connection mode to SFTP if it is in Git mode. (#807)
- Removed field name in reply of `site info --field=<field_name>`. (#811)
- `site redis clear` no longer complains of an inability to find hosts. (#813)
- Machine tokens are now saved in a `~/.terminus/tokens` directory, a sibling to the Terminus cache directory. (#825)
- The `<email>` in `auth login <email>` will now reuse a saved machine token for the account associated with that email address, if present. (#825)
- `TERMINUS_USER` will be used to locate a matching saved machine token before user/password login is attempted. (#825)
- If only one saved token is present, `auth login` will use it when it has no other arguments. (#825)
- If a `drush` or `wp` command exits with any status except for 0, Terminus now exits with that status. (#827)
- Removed "Backup URL:" label from the single-record output of `site backups get`. (#828)
- Added machine_token to the error output blacklist. (#840)
- Status on `workflows list` and `workflows show` now read "running", "succeeded", or "failed". (#850)
- Renamed Environment#log() to Environment#getCodeLog(). (#831)
- `site deploy` will exit with status 1 and the message "There is nothing to deploy." if there are no changes to deploy. (#831)
- When not logged in, the message given now refers the user to create a machine token in order to log in. (#790)

### Fixed
- Fixed bug in Input#orgId. (#812)
- Fixed error appearing in `organizations sites list` when there are no results. (#812)
- Fixed missing-variable error in Request#request which appeared when attempting to sanitize error messages. (#835)
- Fixed log-in admonition if a machine token, TERMINUS_USER, or TEMRINUS_MACHINE_TOKEN are present. (#849)
- Fixed erroneous, old `--[no-]format` tag listing in `help`, replacing it with current `--format=<json|bash|silent>` option. (#854)

### Removed
- `--session=<session_id>` argument has been removed from `auth login`. (#826)
- `logInViaSessionToken()` has been removed from `Auth`. (#826)
- `log()` has been removed from `Environment`. Use new commits property (contains Commits collection) instead. (#831)

## [0.10.1] - 2016-01-12
### Added
- `config/constants.yml` file to contain the default constants for Terminus. (#791)
- Added a `--name=<regex>` filter to `sites list`. Use regex to filter the sites by name. (#802)

### Fixed
- Fixed missing-variable error on site selection prompt. (#809)

### Changed
- Moved Terminus::prompt(), Terminus::promptSecret() to Terminus\Helpers\Input. (#768)
- Removed duplicative Terminus::menu() in favor of Terminus\Helpers\Input::menu. (#768)
- Moved Terminus::line() to Terminus\Outputters\Outputter. (#768)
- Removed dev packages from PHAR file. (#774)
- Updated Symfony to version 3.0.0. Minimum PHP version required to run Terminus is now 5.5.9. (#772)
- `auth whoami` now returns a user profile rather than their UUID. (#763)

### Fixed
- Missing creation dates in site data while using organizations site list command will no longer cause errors. (#766)
- Fixed headers in session token-based login. (#764)
- `site backups load --element=database` no longer errs upon calling the renamed function "backup". (#767)
- `site backups get --latest` bug wherein it was returning the oldest backup, rather than the most recent. (#770)
- `sites aliases` will no longer tell you you have no sites when none of your domains include 'pantheon.io'. (#782)
- `site tags add` now searches for existing tags before adding another. (#771)
- `site redis clear` undefined-variable error has been fixed. (#799)

## [0.10.0] - 2015-12-15
### Added
- New command `workflows show` displays details about a workflow (#687)
- Added back session token-based login. (#693)
- Added initiator data (manual or automated) to `site backups list`. (#716)
- New command `workflows watch` to stream workflow updates (#722)
- New command `site backups get-schedule` shows the scheduled weekly backup day and daily backup time. (#723)
- New command `site backups set-schedule` schedules the daily backup and weekly day. (#724)
- New command `site backups cancel-schedule` cancels the regular backup schedule. (#725)
- New command `organizations team` displays a member list of organizational members. (#726)
- Added an owners file per the Owners Policy on the Chromium Project. (#727)
- New subcommand `site hostnames lookup --hostname=<hostname>` to look up a site and environment by hostname. WARNING: May take a long time to run. (#729)
- New flag `--recursive` on `help` command to show the full details of all subcommands. (#730)
- Environment variable `TERMINUS_SSH_HOST` targets a specific host for `drush` and `wp` commands. (#737)
- Documentation and examples for the use of Terminus as a library. (#738)

### Fixed
- `site backups get` no longer errs when there are no backups. (#690)
- Interactive commands' environment menus now consistently include multidev environments, where applicable. (#701)
- `site wake` (#710)
- `sites create` and `sites import` no longer give warnings about missing $org_id variable. (#733)
- `site backups list` now responds to the `--latest` flag. (#734)
- Changed Backups#isBackupFinished to falsify if backup size is "0". (#734)
- Fixed fatal error which appeared when using `sites aliases`. (#743)
- Fixed missing-variable error when user has no sites while using `sites aliases`. (#743)

### Changed
- Extricated the request logic from TerminusCommand class and moved it to the Request class. (#704)
- Replaced Mustache templates with Twig. (#730)
- Resolved Terminus base class' and Runner's interfunctionality. Terminus can now be used as a library. (#738)
- Drush and WP-CLI commands routed through Terminus must now make use of quotes to pass the command and arguments. (#702)

##[0.9.3] - 2015-11-17
### Added
- `site environments` now includes data on whether environment is initialized yet. (#654)
- Login with Auth0 via `auth login --machine-token=<Auth0 token>` is now available. (#665)
- You can set a machine token via the environment variable TERMINUS_MACHINE_TOKEN. (#665)

### Changed
- Cached sites lists are now keyed to UUID, preventing a previously logged-in user's list from interfering with the currently logged-in user. (#652)
- Terminus now requires PHP version 5.5.0 or greater. (#661)
- Upgraded behavioral testing to Behat 3.0.x. (#670)
- `site workflows` command moved to `workflows list`. (#676)
- Moved command files from `php/commands` to `php/Terminus/Commands` and standardized file names. (#682)
- Added an assets directory, moved ASCII art out of ArtCommand and into assets. (#685)

### Fixed
- Automatic version check disabled for testing. (#643)
- Bad Github API returns for version check now does not cause error. (#643)
- Composer installation does not return stability errors. (#661)

##[0.9.2] - 2015-10-29
### Fixed
- `sites list` no longer capitalizes membership UUIDs. (#642)

### Added
- `sites list` now has an optional `--cached` flag which makes the command return the cached sites data rather than retrieving it anew. (#637)
- `sites mass-update` now can be filtered by `--tag=<tag>`. (Note: `--org=<name|id>` is necessary to use the filter.) (#640)
- `sites mass-update` now has an optional `--cached` tag to optionally prevent retrieving a new sites cache. (#640)
- Environment variables `TERMINUS_PORT` and `TERMINUS_PROTOCOL` now enabled. (#643)

##[0.9.1] - 2015-10-27
### Fixed
- `site backups get` will now find and retrieve backups properly. (#632)
- `sites mass-update` now differentiates between an updated site and one in SFTP mode and warns user appropriately. (#633)

##[0.9.0] - 2015-10-22
### Added
- `site environment-info --site=<site> --env=<env> [--field=<field>]` (#582)
- `site backups get` now has an optional `--file=<filename>` parameter for selection. (#604)

### Fixed
- `site connection-info` Git, MySQL and Redis info now correct (#573)
- Password no longer shows when logging in with some Windows terminal clients (#574)
- No more errors when running Terminus in Windows from directories with spaces in the path. (#575)
- User-type workflows, as used while waiting on `sites create`, now retrieve UUID properly. (#588)
- `site create-env` no longer fails to clone from an environment. (#602)
- `site backups list` filtering by element fixed. (#602)
- Four SSH-based commands now return with unavailable errors: `wp import`, `wp db`, `drush sql-connect`, and `drush sql-sync`. (#607)
- Failure of the API to return a connection mode no longer inhibits its setting. (#616)
- When trying to access an invalid collection member, Terminus now exits instead of having a fatal error. (#615)

### Changed
- Logged errors now exit with -1. (#576)
- `--bash`, `--json`, and `--silent` have been replaced with `--format=<bash|json|silent` (#577)
- `site connection-mode` no longer checks the connection mode. Connection-mode checks are now done using `site environment-info --field=connection_mode` (#583)
- `site connection-mode` is now `site set-connection-mode` and uses the `--mode` flag instead of `--set`. (#583)
- `site import` is now `site import-content` and the --element parameter only accepts "files" and "database" (#516)
- Command failures now have an exit code of "1". (#605)

### Removed
- Removed `site attributes`. Use `site info` for the same effect. (#584)

##[0.8.1] - 2015-09-28
### Changed
- Packagist now indexes this project as `[pantheon-systems/cli](https://packagist.org/packages/pantheon-systems/cli)` (was `terminus/terminus`)
- `site owner` now just returns the owner ID. The --set flag has been removed. Setting is now done by `site set-owner`. (#469)
- `site backup get` flag `--to-directory` is now `--to` and accepts either a directory or a file name (#493)
- `site clear-caches` is now `site clear-cache` (#353)
- `site upstream-updates` is now `site upstream-updates <list|apply>`. The --update flag was removed. The --accept-upstream flag was added (#473)
- `sites create-from-import` is now `sites import` (#465)
- `sites create` no longer accepts the --import flag. Use `sites import` instead (#465)
- `site service-level` is now `site set-service-level` and uses the --level flag instead of --set to indicate new level. Service level checks now done by using `site info --field=service_level` (#507)
- Makes API calls to host 'dashboard.pantheon.io' instead of 'dashboard.getpantheon.com' (#508)
- `site deploy` limited to test or live environment. --from removed. --clone-live-content changed to --sync-content (#463)
- Changed parameter `--env` on `site create-env` to `--to-env` (#514)

###Added
- `site set owner --site=<site> --set=<owner>` sets the site owner to the given user ID. (#499)
- `site tags list --site=<site> --org=<org>` will list tags associated between that organizaiton and site (#517)
- Terminus now checks for software updates once per week and will log to info if one is available. (#512)

###Fixed
- `site organizations add|remove` no longer crashes when given an invalid organization. (#515)
- Filename for aliases as shown in `sites aliases` help text (#522)
- Element selection on `sites backups` (#532)
- Fixed regression on backups (#525)
- Fixed PHP 5.3.x compatibility (#541)

##[0.8.0] - 2015-09-15
###Added
- Environment variable TERMINUS_LOG_DIR will save all logs to file in the given directory. (#487)

###Fixed
- Undefined property "framework" error when running SitesCache functions (#433)
- Split Terminus logger between KLogger and Outputter, fixed JSON and bash outputs

##[0.7.1] - 2015-08-21
###Fixed
- PHP 5.3 incompatibility

##[0.7.0] - 2015-08-20
### Added
- `site delete` command will delete a site (moved from `sites delete`, which has been deprecated) (#370)
- `organizations sites --tag=<name>` filters list of sites by tag
- `site team change-role` Changes an existing member's role (For sites with the proper access level) (#388)
- `site team add-member` now has a required --role flag (For sites with the proper access level) (#388)
- `site delete-branch` will delete a multidev environment's branch (For sites with multidev access) (#395)
- `site delete-env` now has an optional --remove-branch flag (#395)
- Environment variables for --site (TERMINUS_SITE), --org (TERMINUS_ORG), --env (TERMINUS_ENV), and user (TERMINUS_USER). User may import these themselves, or add them to the .env file in the user's current directory. (#407)
- `site tags <add|remove> --site=<site> --org=<org> --tag=<tag>` command will add tags to an organization site (#417)
- `site workflows` commmand will show all workflows run on the site and their statuses (replaces `site jobs` and `site notifications`) (#412)

### Fixed
- `organizations sites` shows all the organization's sites, not just the first 100 (#371)

### Changed
- `site wipe` asks for confirmation (#382)
- `backup get` will not offer in-progress/incomplete backups for download (#386)
- `backup list` identifies 0-byte backups as "Incomplete" (#386)
- `site clone-env` is now `site clone-content`. Flags have changed from inclusive --db and --files to exclusive --db-only and --files-only and defaults to cloning both. (#403)
- `products` is now `upstreams` (#404)
- The `--product` flag on `sites create` is now `--upstream` (#404)
- `site backup` is now `site backups` (#416)
- The `--nocache` flag has been removed (#415)

### Deprecated
- `sites delete` will be removed in v1.0.0 (#370)
- `site jobs` will be removed in v0.7.0 (#412)
- `site notifications` will be removed in v0.7.0 (#412)

###Removed
- Removed --branch-create flag from `sites code` (#505)

##[0.6.1] - 2015-08-11
### Fixed
- `site deploy` will not overwrite the Live environment's content (#373)

### Changed
- `site deploy` has a `--clone-live-environment` flag for copying Live content into Test (#373)

### Deprecated
- `site deploy` `--from` flag has been deprecated and is non-functional

##[0.6.0] - 2015-08-10
### Added
- `cli console` (Instantiates a console within Terminus)
- `site init-env` (Initializes new test or live environments on Pantheon)
- `site merge-from-dev` (Merges master/dev environment into a multidev environment)
- `site merge-to-dev` (Merges a multidev environment into master/dev)
- `sites cache` (Lists sites in cache)
- `sites mass-update` (Runs upstream updates on all dev sites)
- Element flag to `site import` (Select specific element[s] to import)
- Behavior tests
- QA report tests
- Linter tools
- CHANGELOG.txt

### Fixed
- `site import`
- `site team list`
- Password display on login failure
- 100x loop on workflow failure

### Changed
- Dashboard URL given by `site dashboard` from https://dashboard.getpantheon.com/… to https://dashboard.pantheon.io/…
- `sites create` to make org parameter optional
- Dependencies
- README

### Deprecated
- Flag --nocache
