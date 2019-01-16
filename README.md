# terminus : Pantheon's Command-Line Interface
 
## Status
[![Build Status](https://travis-ci.org/pantheon-systems/terminus.svg?branch=master)](https://travis-ci.org/pantheon-systems/terminus)
[![Windows CI](https://ci.appveyor.com/api/projects/status/niiheng08p25mgnm?svg=true)](https://ci.appveyor.com/project/greg-1-anderson/terminus)
[![Coverage Status](https://coveralls.io/repos/github/pantheon-systems/terminus/badge.svg?branch=master)](https://coveralls.io/github/pantheon-systems/terminus?branch=master)
 
## About
Terminus is Pantheon's Command Line Interface (CLI), providing at least equivalent functionality to the Pantheon's
browser-based Dashboard and easier scripting.

If you would like to contribute, pull requests are welcome!

## The Manual
Our documentation is kept in the Terminus Manual, located here: https://pantheon.io/docs/terminus

## Dependencies
### Required
- A command-line client
- PHP version 5.5.38 or later
- [PHP-CLI](http://www.php-cli.com/)
- [PHP-CURL](http://php.net/manual/en/curl.setup.php)

Once you have at least the requirements installed, you can install Terminus via Composer or Git. Additionally, you may want to install the optional software below to enhance your use of Terminus:

### Recommended
- [Composer](https://getcomposer.org/doc/00-intro.md)
- [Drush](http://docs.drush.org/en/master/install/) (Useful to run incompatible-with-Terminus Drush commands)
- [WP-CLI](http://wp-cli.org/) (Useful to run incompatible-with-Terminus WP-CLI commands)
- [Git](https://help.github.com/articles/set-up-git/)

You can install Terminus just about anywhere on your system. In this README, we'll use `/install/location` to stand in for your chosen installation location.

## Installation
### Installing via the Terminus installer
Run this in your Terminal client:
```bash
curl -O https://raw.githubusercontent.com/pantheon-systems/terminus-installer/master/builds/installer.phar && php installer.phar install
```
For more information on installation options or to report an issue with this method, please see the [Terminus Installer README.md file](https://github.com/pantheon-systems/terminus-installer).

### Installing with Composer
Run this in your terminal client:
```bash
cd /install/location ; composer require pantheon-systems/terminus
```
If you are having issues installing, please see to it that any old versions of Terminus are removed by using
```bash
composer remove pantheon-systems/terminus
```
before requiring it.

### Installing with Git
To install with Git and use Terminus HEAD, you should clone this repository and run Terminus directly. If you would
like to contribute to the Terminus source, this is the way you should install it. You will require Composer for this installation.

- Clone the repository. If you plan on contributing to the project, create a fork and clone the fork instead:
```bash
cd /install/location ; git clone https://github.com/pantheon-systems/terminus.git terminus
```
- Install the Composer dependencies:
```bash
cd terminus ; composer install
```

You can now run the bleeding-edge version of Terminus via:
```bash
bin/terminus
```

## Updating
### Updating via the Terminus installer
Run this in your Terminal client:
```bash
curl -O https://raw.githubusercontent.com/pantheon-systems/terminus-installer/master/builds/installer.phar && php installer.phar update
```
For more information on update options or to report an issue with this method, please see the [Terminus Installer README.md file](https://github.com/pantheon-systems/terminus-installer).

### Updating with Composer
Run this in your terminal client:
```bash
cd /install/location ; composer update
```

### Updating with [Homebrew](http://brew.sh/) (for Macs)
Update Terminus with this command:
```bash
brew upgrade homebrew/php/terminus
```

### Updating with Git
To update with Git and use Terminus HEAD, you should update this repository and then update its dependencies via Composer.

- Update the repository:
```bash
cd /install/location/terminus ; git pull
```
- Update the Composer dependencies:
```bash
composer update
```

**Optionally**, for ease of development we suggest aliasing, setting the PATH in the bash configuration file, or
symlinking to it. This file is located at `~/.bashrc` on Linux systems and at `~/.bash_profile` on Mac.
#### Alias
```bash
alias terminus="/install/location/terminus/bin/terminus"
```
Once you source the file or restart your terminal client, you can now make use of Terminus using
```bash
terminus
```
#### Exporting the Path
```bash
export PATH="$PATH:/install/location/terminus/bin"
```
Once you source the file or restart your terminal client, you can now make use of Terminus using
```bash
terminus
```
#### Symlinking
Adding a symlink to `/install/location/terminus/bin/terminus` inside your bin directory will work.

## Authentication
To get started with Terminus, you must first authenticate:
```bash
terminus auth:login --machine_token=xxxxxxxx
[notice] Logging in via machine token
```
If you are planning to run WP-CLI or Drush commands through Terminus, please
[upload an SSH key](https://pantheon.io/docs/ssh-keys/#add-your-ssh-key-to-pantheon) for the user that will be executing Terminus.
 
## Running
Commands in Terminus follow a predictable pattern:
```bash
terminus command:subcommand:subcommand param param --option=value --option
```
- `terminus` is the name of the application.
- `command[:subcommand[:subcommand]]` is the name of the command to run. Terminus commands may consist of only a `command`.
- `param` are parameters, and are almost always required for operating the command.
- `option` are options, and are never required to run a command. They may or may not require a value.

## Runtime Configuration
### Setting default user, site, environment, etc. and Dotenv
Terminus can use certain environment variables to set certain default values when invoking commands. Check the 
`config/constants.yml` file for the names of variables which can be set. Do not alter this file - add global settings to
your `~/.terminus/config.yml` file or export them in your terminal client.

Terminus also has built-in support for [PHP Dotenv](https://github.com/vlucas/phpdotenv), which provides an alternative
to manually exporting environment variables. This involves defining environment variables within a `.env` file that
Terminus will automatically use when invoked within that working directory.

## Known Issues/Limitations
- Terminus will not offer you options for selection when parameters are not provided. This will be added in the future.
 
## Developing & Contributing
1. See the [CONTRIBUTING](CONTRIBUTING.md) document.
2. Create an issue on this repository to discuss the change you propose should be made.
3. Fork this repository.
4. Clone the forked repository.
5. Run `composer install` at the repository root directory to install all necessary dependencies.
6. Make changes to the code.
7. Run the test suite. The tests must pass before any code will be accepted.
8. Commit your changes and push them up to your fork.
9. Open a pull request on this repository to merge your fork.

Your pull request will be reviewed by Pantheon and changes may be requested before the code is accepted.

## Testing
Tests are run via the `.scripts/test.sh` script. Components thereof can be run as follows:

- `composer cs` runs the code sniffer to ensure all code is appropriately formatted.
- `composer phpunit` runs the PHPUnit unit tests.
- `composer behat` runs the Behat feature tests
 
## Support
Please make ready the steps to reproduce the issue, outputs, pertinent information about your system, and what you
believe the correct reaction of the system ought to be. Reporting of issues encountered should happen in one of two
ways:

### Information that helps us help you
If we cannot duplicate an issue, we cannot resolve it. Giving us as much information about the problem you're having
as you can will help reduce the amount of time between an issue's being reported and being resolved. This is typically
the most helpful information:

- The result of running `terminus self:info`.
- The output of the issue in debug mode. (Run the command with `--vvv` appended to it.)

### If yours is a problem with Terminus itself:
1. Search [Terminus' issues on GitHub](https://github.com/pantheon-systems/terminus/issues) to see whether another
user has reported the same issue you are experiencing.
2. If the problem you are experiencing is not in the issues, you can open a new issue. Please include the helpful
information you have gathered.
3. If you find your problem in an issue, feel free to add your issue information in the comments and/or subscribe to
the issue's notifications.

### If the problem is with the Pantheon platform
Head over to [your support tickets in the Pantheon Dashboard](https://dashboard.pantheon.io/users/#support) and
submit a new issue ticket. Please include the helpful information you have gathered.
 
## Managing Third-Party Libraries
Dependencies are easily updated by Composer. To update this codebase:

0. Check Gemnasium (see the button under the status heading above) to see if there are dependencies requiring an update.
1. Check out a new branch off of an up-to-date copy of master.
2. Run `composer update` at the repository root directory.
3. Run the test suite. If there are errors, address them.
4. Commit the changes, push the branch, and create a pull request for the update.
 
## Deployment
To deploy a new version of Terminus:

0. Ensure that the version numbers atop `CHANGELOG.md` and in `config/constants.yml` are updated to the new version.
1. Create a release in GitHub.
2. Copy all `CHANGELOG.md` entries for the new version into the description of the release.
3. Tag the new release with its version number.
4. Release it. It will become automatically available to the public via Packagist and the [Terminus Installer](https://github.com/pantheon-systems/terminus-installer).
 
## Debugging
- Run Terminus with the `-vvv` option to get debug output.
- If you are getting `PHP Fatal error:  Uncaught exception 'ReflectionException' ...`, install php-xml.
- If you are getting `cURL error 60: SSL certificate problem: ...`, download a [cacert.pem](https://curl.haxx.se/ca/cacert.pem)
file and add `curl.cainfo = "[path_to_file]\cacert.pem"` to your `php.ini`. If using XAMPP, you can add this to your
`xampp\php\extras\ssl` directory.
