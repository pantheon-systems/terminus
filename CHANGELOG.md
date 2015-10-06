#Change Log
All notable changes to this project starting with the 0.6.0 release will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org)

##Master
### Fixed
- `site connection-info` Git, MySQL and Redis info now correct (#573)
- Password no longer shows when logging in with some Windows terminal clients (#574)
- No more errors when running Terminus in Windows from directories with spaces in the path. (#575)

### Changed
- Logged errors now exit with -1. (#576)
- `--bash`, `--json`, and `--silent` have been replaced with `--format=<bash|json|silent` (#577)

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
