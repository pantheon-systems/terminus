# terminus : Pantheon's Command-Line Interface

## Status

[![Commit Build](https://github.com/pantheon-systems/terminus/actions/workflows/ci.yml/badge.svg?branch=v3.0)](https://github.com/pantheon-systems/terminus/actions/workflows/ci.yml)

## About

Terminus is Pantheon's Command Line Interface (CLI), providing at least equivalent functionality to the Pantheon's
browser-based Dashboard and easier scripting.

If you would like to contribute, pull requests are welcome!

## Rollback

Terminus ships as a release, not a running service. A tag on `4.x` publishes a GitHub release with `terminus.phar` attached, and a PR on [pantheon-systems/homebrew-external](https://github.com/pantheon-systems/homebrew-external) bumps the Homebrew formula. Merging to `4.x` deploys nothing. Releasing and rolling back are described in full in [DEPLOYMENT.md](DEPLOYMENT.md#rollback).

### When to roll back

- New issues on [the issue queue](https://github.com/pantheon-systems/terminus/issues), or reports in `#ask-hosting` or from Support, describe a regression that started with the latest release.
- The release's `terminus.phar` doesn't run: `php terminus.phar --version` fails or prints the wrong version.
- A command that works with the previous release fails with the new one, e.g. `terminus auth:whoami`.

Fixing forward with a patch release is the norm. Pull a release back when it is broken badly and the fix will take more than a few hours.

### Roll back a release

Before you start, note the previous good version, `<PREVIOUS_VERSION>`.

1. Mark the bad release as a pre-release. Don't delete or move its tag:
   ```bash
   gh release edit <BAD_VERSION> -R pantheon-systems/terminus --prerelease
   ```
   GitHub's latest release falls back to `<PREVIOUS_VERSION>`, so `terminus self:update` stops offering `<BAD_VERSION>`. The update notice follows within its 7-day cache.
2. Open a PR on `pantheon-systems/homebrew-external` that sets `url` and `sha256` in `Formula/terminus.rb` back to `<PREVIOUS_VERSION>`, and get it merged. Use the values from that file's history, or hash the asset: `curl -sL https://github.com/pantheon-systems/terminus/releases/download/<PREVIOUS_VERSION>/terminus.phar | shasum -a 256`.
3. Ship the fix as the next patch release. Never reuse or move a tag.

### What a rollback does not undo

Nobody's installed copy goes back on its own. `self:update` never downgrades, and `brew upgrade` won't either. Tell affected users to reinstall:

- PHAR: `curl -L https://github.com/pantheon-systems/terminus/releases/download/<PREVIOUS_VERSION>/terminus.phar --output <PATH_TO_TERMINUS>`
- Homebrew, once the formula revert is merged: `brew update && brew reinstall terminus`
- Composer: pin the constraint to `<PREVIOUS_VERSION>`

CI images built in the meantime (`docker-build-tools-ci` runs `self:update` at build time) keep the bad version until they're rebuilt.

### Verify the rollback

- `gh api repos/pantheon-systems/terminus/releases/latest --jq .tag_name` prints `<PREVIOUS_VERSION>`.
- `Formula/terminus.rb` on the tap's `main` points at `<PREVIOUS_VERSION>`.
- `terminus self:update` on `<BAD_VERSION>` prints `No update available`, and after reinstalling, the reported symptom is gone.

### Who to page

The owning team is Developer Experience (`@pantheon-systems/developer-experience`). Ask in `#ask-hosting`. Nobody is paged for Terminus.

## The Manual

Our documentation is kept in the Terminus Manual, located here: https://pantheon.io/docs/terminus

## Requirements

| Operating System       | Version    |
|------------------------|------------|
| MacOS                  | 10.14+     |
| Ubuntu                 | Latest LTS |
| Windows + WSL + Ubuntu | TBD        |

### Package Manager

- [apt](https://ubuntu.com/server/docs/package-management) for Ubuntu/WinWSL-Ubuntu

- [Homebrew](https://brew.sh) for mac

#### Required Packages

These packages are required to take full advantage of Terminus.

- [Composer 2](https://getcomposer.org) (Needed for the plugin manager component)

- [PHP](https://www.php.net) (v7.4+)

- [Git](https://help.github.com/articles/set-up-git/) (May be needed for the plugin manager component)

#### Recommended Packages

- [Drush](http://docs.drush.org/en/master/install/) (Useful to run incompatible-with-Terminus Drush commands)

- [WP-CLI](http://wp-cli.org/) (Useful to run incompatible-with-Terminus WP-CLI commands)


## Installation

### Mac OS:

Terminus is published as a package under pantheon-systems/external. To install it, you should run:

```
brew install pantheon-systems/external/terminus
```

### Ubuntu / WinWSL+Ubuntu:

`*** TBD ***`

### Other installation methods

Refer to the [Terminus manual](https://pantheon.io/docs/terminus/install#install-terminus) for other installation methods.
