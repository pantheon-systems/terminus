# terminus : Pantheon's Command-Line Interface

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

- [Drush](http://docs.drush.org/en/master/install/) (Useful to run incompatible-with-Terminus Drush commands)

- [WP-CLI](http://wp-cli.org/) (Useful to run incompatible-with-Terminus WP-CLI commands)


## Installation

### Mac OS:

`> brew tap {INJECT_BREW_TAP}`

`> brew install {INJECT_BREW_PACKAGE}`

### Ubuntu / WinWSL+Ubuntu:

`{INJECT_DEBIAN_PACKAGE_INSTALL}`
