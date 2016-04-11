Terminus
============

[![Build Status](https://travis-ci.org/pantheon-systems/terminus.svg?branch=master)](https://travis-ci.org/pantheon-systems/terminus) [![Dependency Status](https://gemnasium.com/pantheon-systems/terminus.svg)](https://gemnasium.com/pantheon-systems/terminus)
[![Coverage Status](https://coveralls.io/repos/github/pantheon-systems/terminus/badge.svg?branch=master)](https://coveralls.io/github/pantheon-systems/terminus?branch=master)

Terminus is Pantheon's Command Line Interface (CLI), providing equivalent functionality to the Pantheon Web Dashboard and easier scripting.

If you would like to contribute, pull requests are welcome!

Installation
------------

**Requirements:**
- PHP version 5.5.9 or later
- [PHP-CLI](http://www.php-cli.com/)
- [PHP-CURL](http://php.net/manual/en/curl.setup.php)

Once you have at least the requirements installed, you can install Terminus via Composer, cURL, or Git. Additionally, you may want to install the optional software below to enhance your use of Terminus:

**Optional but recommended:**
- [Drush](http://docs.drush.org/en/master/install/) (Useful to run incompatible-with-Terminus Drush commands)
- [WP-CLI](http://wp-cli.org/) (Useful to run incompatible-with-Terminus WP-CLI commands)
- [Composer](https://getcomposer.org/doc/00-intro.md)
- [Git](https://help.github.com/articles/set-up-git/)

> You can install Terminus just about anywhere on your system. In this README, we'll use `/install/location` to stand in for your chosen installation location.

####Installing with Composer

The fastest and easiest way to install Terminus is via Composer. Simply run this in your terminal client:
```
composer require pantheon-systems/terminus
```

####Installing with cURL

Run this in this in your terminal client:
```bash
curl https://github.com/pantheon-systems/terminus/releases/download/0.11.1/terminus.phar -L -o /usr/local/bin/terminus && chmod +x /usr/local/bin/terminus
```

####Installing with [Homebrew](http://brew.sh/)(for Macs)

If you do not have `homebrew-php` already tapped, here are the commands for the taps:
```bash
brew tap homebrew/dupes
brew tap homebrew/versions
brew tap homebrew/php
```

And after you're all tapped out, install Terminus with this command:
```bash
brew install terminus
```

####Installing with Git
To install with Git and use Terminus HEAD, you should clone this repository and run Terminus directly. If you would like to contribute to the Terminus source, this is the way you should install it. You will require Composer for this installation.

1. Clone the repository. If you plan on contributing to the project, create a fork and clone the fork instead.
  ```bash
  cd /install/location
  git clone https://github.com/pantheon-systems/terminus.git terminus
  ```
Or replace /install/location with the directory to which you would like to install.

2. Install the Composer dependencies.
  ```bash
  cd terminus
  composer install
  ```
You can now run the bleeding-edge version of Terminus via:
  ```bash
  bin/terminus
  ```

**Optionally**, for ease of development, we suggest aliasing or setting the path to this script in your Bash configuration file. This file is located at ~/.bashrc on Linux and ~/.bash_profile on Mac.
```bash
alias terminus="/install/location/terminus/bin/terminus"
```
or
```bash
export PATH="$PATH:/install/location/terminus/bin"
```
Once you source the file or restart your terminal client, you can now make use of Terminus using
```bash
terminus
```

Authentication
--------------

To get started with Terminus, you must first authenticate:
```bash
terminus auth login
Your email address?: user@pantheon.io
Your dashboard password (input will not be shown)
[1969-07-20 20:18:00] [info] Logging in as user@pantheon.io
[1969-07-21 02:56:00] [info] Saving session data
```

Tab completion
--------------
Terminus also comes with a tab completion script for Bash. Just download [terminus-completion.bash](https://github.com/pantheon-systems/terminus/blob/master/utils/terminus-completion.bash) and source it from `~/.bash_profile`:

```bash
source /FULL/PATH/TO/terminus-completion.bash
```

(Don’t forget to run `source ~/.bash_profile` afterwards)

Setting default user, site, environment, etc. and Dotenv
--------------

Terminus can use certain environment variables to set certain default values when invoking commands. For example, by exporting the environment variable `TERMINUS_SITE=<sitename>` Terminus will automatically dispatch all commands against that site allowing you to omit the `--site=<sitename>` in your commands. A list of available environment variables is listed in the [`.env-sample` file](https://github.com/pantheon-systems/terminus/blob/master/.env.example).

Terminus also has built-in support for [PHP Dotenv](https://github.com/vlucas/phpdotenv), which provides an alternative to manually exporting environment variables. This involves defining environment variables within a `.env` file that Terminus will automatically use when invoked within that working directory.

Support
------------
Please make ready the steps to reproduce the issue, outputs, pertinent information about your system, and what you believe the correct reaction of the system ought to be. Reporting of issues encountered should happen in one of two ways:

**Information that helps us help you:**

If we cannot duplicate an issue, we cannot resolve it. Giving us as much information about the problem you're having as you can will help reduce the amount of time between an issue's being reported and being resolved. This is typically the most helpful information:

- The result of running `terminus cli info`.
- The output of the issue in debug mode. (Run the command with `--debug` appended to it.)
- The name and version of the OS you're seeing the issue on.

**If yours is a problem with Terminus itself:**

1. Search [Terminus' issues on GitHub](https://github.com/pantheon-systems/terminus/issues) to see whether another user has reported the same issue you are experiencing.
2. If the problem you are experiencing is not in the issues, you can open a new issue. Please include the helpful information you have gathered.
3. If you find your problem in an issue, feel free to add your issue information in the comments and/or subscribe to the issue's notifications.

**If the problem is with the Pantheon platform:**

Head over to [your support tickets in the Pantheon Dashboard](https://dashboard.pantheon.io/users/#support) and submit a new issue ticket. Please include the helpful information you have gathered.

Contributions
------------
Here are steps to follow if you would like to contribute to Terminus:

1. Fork the repository.
2. Add your changes. Please add tests as necessary. You can check your syntax for coherence to our standards by running:
  ```bash
  cd /install/location/terminus
  ./scripts/lint.sh
  ```
And run your tests via:
  ```bash
  cd /install/location/terminus
  ./scripts/test.sh
  ```
Please also run the internal documentation generator before committing your changes. This keeps the documentation always up-to-date:
  ```bash
  cd /install/location/terminus
  php utils/make-docs.php
  ```

3. Open a pull request on GitHub so that we may evaluate and merge your changes.

Credits
------------
* We have leaned heavily on the work of [WP-CLI](http://wp-cli.org/) in architecting this command line utility with an object-oriented structure.
* We've also (obviously) been greatly inspired by [Drush](http://drush.ws/).

Further Reading
------------
* [Terminus' Wiki](https://github.com/pantheon-systems/terminus/wiki)
* [Usage](https://github.com/pantheon-systems/terminus/wiki/Usage)

If you are looking for the precursor to this project, which is now deprecated see [https://github.com/pantheon-systems/terminus-deprecated](https://github.com/pantheon-systems/terminus-deprecated)
