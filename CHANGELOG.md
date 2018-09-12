# Change Log
All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org)

## 1.9.0 - 2018-09-11
### Added
- Added a `hide_git_mode_warning` option to disable the warning presented when users run Drush or WP-CLI commands on Pantheon sites that are in git mode.  (#1882)

### Fixed
- Prevent spurious dependency validation failures with Terminus plugins that have `dev` components named in their composer.lock file that have not been installed. (#1880)
- Removed the prompt displayed when running Drush or WP-CLI commands on a Pantheon server to avoid locking up auotmation scripts. (#1881)
- Set minimum PHP version to 5.5.38; some earlier versions of PHP 5.5 do not work with Terminus. (#1875)
- Fixed php warning when ssh key is missing its comment field. (#1843)

## 1.8.1 - 2018-06-08
### Fixed
- Fixed bug wherein messages that are passed in as arrays to TerminusException cause failure. (#1863)

## 1.8.0 - 2018-03-29
### Added
- `alpha:env:metrics` command has been added. (#1835)

### Fixed
- Prevented missing data about a site's upstream from preventing functioning of the SiteUpstream model. (#1833)

## 1.7.1 - 2018-02-27
### Fixed
- Fixed `workflow:watch` command by preventing cached data from being pulled. (#1827)

## 1.7.0 - 2018-01-29
### Added
- Added a `last_frozen_at` field to the output of `site:info` and the `Site::serialize()` function. (#1788)

## 1.6.1 - 2017-11-01
### Changed
- Added exponentially backing-off retries to HTTP requests. (#1782)

## 1.6.0 - 2017-10-11
### Added
- `domain:dns` has returns a new field, `detected_value`, which indicates the live DNS settings for a given domain. (#1756)
- `domain:dns` has returns a new field, `status`, which indicates whether live DNS settings for a given domain match the recommended setting. (#1756)
- Added new command `site:upstream:clear-cache` to clear code caches on a site. (#1762)
- `TerminusCollection::reset()` to reset the model array (after filtering or sorting). (#1779)

### Fixed
- Changed the Domains collection to use a newer API call, fixing `domain:dns`. (#1756)
- Fixed operations for sites that have been unfrozen. (#1766)

### Removed
- Removed the now-obsolete `Domains::setHydration(string)` function. (#1756)
- Removed `TerminusCollection::listing(string, string)`. (#1779)
- Removed the `Loadbalancer` model. (#1779)
- Removed the `Loadbalancers` collection. (#1779)

### Changed
- `multidev:create` is now checked for whether the site is frozen before attempting to execute. (#1761)
- `import:database` is now checked for whether the site is frozen before attempting to execute. (#1761)
- Checks for frozen sites will now throw errors on dev and multidev environments as well as test and live. (#1761)
- `domain:list` now lists `id`, `type`, and `deleteable` attributes. (#1763)
- `https:info` now lists `id`, `type`, `status`, `status_message`, and `deleteable` attributes. (#1763)
- `https:info` emits a `RowsOfFields` object instead of a `PropertyList`. (#1763)
- `domain:dns` now emits an info log instead of a warning. (#1772)
- `TerminusCollection::fetch()` no longer accepts an array of options. Use `setData(array)` to pass in data and `setFetchArgs(array)` for the same functionality. (#1779)

### Deprecated
- `Workflow::operations()` is deprecated. Use the `Workflow::getOperations()` to retrieve workflow operations. (#1769)

## 1.5.0 - 2017-08-17
### Changed
- Updated the name of the `longname` field output by `upstream:list` to `label`. (#1747)
- Updated the name of the `longname` field output by `upstream:info` to `label`. (#1747)
- Upstreams of types `core` and `custom` are the only ones which appear by default when using `upstream:list`. (#1747)
- The `--org` option of the `site:list` command now defaults to `"all"` instead of `null`, but its behavior is unchanged. (#1747)
- The `role` parameter of the `site:team:add` command defaults to `team_member`. (#1750)

### Added
- Added a `machine_name` field to the output of `upstream:list`. (#1747)
- Added a `organization` field to the output of `upstream:list`. (#1747)
- Added a `machine_name` field to the output of `upstream:info`. (#1747)
- Added a `organization` field to the output of `upstream:info`. (#1747)
- Added a `repository_url` field to the output of `upstream:info`. (#1747)
- Added a `org:upstream:list` command to list the upstreams of a specific organization. (#1747)
- Added an `--org` option to `upstream:list` to list the upstreams of a specific organization. (#1747)
- Added an `--all` option to list upstreams of all types in the output of `upstream:list`. (#1747)
- Added a `--framework` option to `upstream:list` to filter the list by framework. (#1747)
- Added a `--name` option to `upstream:list` to filter the list by name regex. (#1747)

### Removed
- Removed the `category` field from the output of `upsteram:info`. (#1747)

### Fixed
- The `org` option of `site:create` now works with machine names and labels as well as UUIDs. (#1747)
- If the `change_management` feature is not enabled on a site, no warning is displayed only if the `role` has been supplied and is not `team_member`. (#1750)

## 1.4.1 - 2017-07-17
### Fixed
- Corrected the help text of `import:site`/`site:import` to call the params params rather than options. (#1718)
- Pin the version of reflection-docblock to prevent syntax-checking problems with @usage tags. (#1740)

### Added
- Added a Collection::filter(callable) function. (#1725)
- Added a `frozen` column to the output of `org:site:list`. (#1726)

## 1.4.0 - 2017-06-07
### Fixed
- Removed the element option's erroneous "all" value from `backup:get`, changed its default to "files". (#1705)

### Added
- Added an experimental `site:upstream:set` command to switch a site's upstream. (#1713)

## 1.3.0 - 2017-04-20
### Added
- `env:commit` now has a `--force` option to force a commit even if no changes are found. (#1115)

## 1.2.1 - 2017-04-11
### Fixed
- Corrected the command to be used to update Terminus displayed by the `UpdateChecker`. (#1687)

## 1.2.0 - 2017-04-10
### Changed
- `Backup::getDate()` now returns a Unix datetime instead of a formatted date. (#1676)

### Added
- The `backup:info` command has been added. (#1676)
- Added expiration dates to backups in `backup:list`. (#1676)
- `Backup::getExpiry()` calculates the Unix datetime of a backup's expiry. (#1676)

### Fixed
- Updates to dependencies have been applied. (#1675)
- Fixed ambiguous text in `env:commit` and `env:diffstat` help description. (#1685)

## 1.1.2 - 2017-03-31
### Changed
- Reenabled the `self:console` command in PHP 7.1. (#1664)
### Fixed
- Corrected typo in `aliases` command which prevented the authorization hook from working on it. (#1663)
- Updated to match changes made to Config class in Robo 1.0.6. (#1670)

## 1.1.1 - 2017-03-09
### Fixed
- composer.json file now has its `bin` property set to include `bin/terminus`. (#1656)

## 1.1.0 - 2017-03-09
### Added
- Added an `--element=` option to `backup:list`. (#1563)
- Added the label column to `org:list`'s output. (#1612)
- Added the `upstream:updates:status` command to report whether any site environment is outdated or current. (#1654)

### Changed
- `self:cc` now acts to delete all files in the command cache directory. (#1569)
- `env:clone-content` and `env:deploy` now refuse to clone from uninitialized environments. (#1608)
- Encapsulation of the properties of models and collections has been tightened. Please use getter and setter methods to access them. (#1615)
- The column labeled as `name` in `org:list`'s output now contains the machine name of an organization. (#1612)
- Any command using an `organization` parameter or `org` option now accepts an organization's UUID, name, and label. (#1612)
- The first parameter of `SiteOrganizationMemberships::create($org, $role)` is now an Organization object. (#1612)

### Deprecated
- The `element` parameter on `backup:list` is deprecated. Use the `--element=` option instead. (#1563)
- The `wait()` function of `Workflows` is deprecated. Use `checkStatus()` instead. (#1584)
- The `User::getOrgMemberships()` is deprecated. Use `User::getOrganizationMemberships()` instead. (#1613)

### Fixed
- Fixed the base branch in the URL when checking for upstream updates. (#1581)
- Fixed `new-relic:info` by changing `NewRelic::serialize()` to fetch its data before attempting to return it. (#1648)
- Removed login information from the debug output. (#1642)

## 1.0.0 - 2017-01-20
### Added
- Added `--to=` option to `backup:get` to allow specifying of a local download location. (#1520)

### Fixed
- Fixed `backup:restore`. (#1529)
- Fixed `env:wake` to target domains with zone names. (#1530)
- Fixed `env` commands to not err when the site is frozen and the test or live environment is to be accessed. (#1537)

### Changed
- Clear cache no longer deletes stored machine tokens. Logout now deletes stored machine tokens. (#1542)
- Terminus now checks for new versions after every command run. (#1523)
- `site:create` now checks to see whether a site name is taken before attempting to create it. (#1536)

### Removed
- Removed framework type check from `drush` and `wp` commands. (#1521)

## 1.0.0-beta.2 - 2017-01-10
### Fixed
- Fixed fatal error by adding back the use statement for ProcessUtils in SSHBaseCommand. (#1494)
- Pinned PHP-VCR version to 1.3.1 due to issues with turning PUT into POST. (#1501)

## 1.0.0-beta.1 - 2016-12-21
### Changed
- Moved to Symfony Console
- Updated command structure. Please see [https://pantheon.io/docs/terminus/commands/compare](https://pantheon.io/docs/terminus/commands/compare) for updates.

_Terminus version v1.0 and later introduces a new command line and argument structure that is incompatible with any custom scripts that use 0.x Terminus or older plugins that you may be using.
Please consider the impact to your automation scripts and plugins before upgrading to Terminus v1.0._
