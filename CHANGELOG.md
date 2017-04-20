# Change Log
All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org)

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
