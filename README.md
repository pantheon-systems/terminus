Pantheon CLI
============

[![Build Status](https://travis-ci.org/pantheon-systems/cli.svg?branch=master)](https://travis-ci.org/pantheon-systems/cli) [![Dependency Status](https://gemnasium.com/pantheon-systems/cli.svg)](https://gemnasium.com/pantheon-systems/cli)

The Pantheon Command Line Interface is a successor to the "Terminus" project, which provides access to the Pantheon Platform via Drush. While Terminus has provided great value (and we're going to keep the name in the product,) we felt that it was important to have a first-class standalone CLI toolkit:

- That we could distribute as an independent executable.
- Which could "wrap" other commands that run on the platform.

If you would like to contribute, pull requests are welcome!

Installation
------------
For more advanced installation instructions, please see [the installation page of our Wiki](https://github.com/pantheon-systems/cli/wiki/Installation).

**Requirements:**
- PHP version 5.3.2 or later
- [PHP-CLI](http://www.php-cli.com/)
- [PHP-CURL](http://php.net/manual/en/curl.setup.php)

Installing Terminus is simple. Ensure that your system has and can run the required software above and run this in your favorite terminal client:
```bash
curl https://github.com/pantheon-systems/cli/releases/download/0.6.1/terminus.phar -L -o /usr/local/bin/terminus && chmod +x /usr/local/bin/terminus
```

To get started with Terminus, you must first authenticate:
```bash
terminus auth login
Your email address?: user@pantheon.io
Your dashboard password (input will not be shown):
Logging in as user@pantheon.io
Success!
```

Tab completion
--------------
Terminus also comes with a tab completion script for Bash. Just download [terminus-completion.bash](https://github.com/pantheon-systems/cli/blob/master/utils/terminus-completion.bash) and source it from ``~/.bash_profile`:

```bash
source /FULL/PATH/TO/terminus-completion.bash
```

(Don’t forget to run `source ~/.bash_profile` afterwards)

Development
------------
To use Terminus HEAD, you should clone this repository and run Terminus directly. Be sure you have the dependencies below installed.

**Requirements:**
- PHP version 5.3.2 or later
- [Composer](https://getcomposer.org/doc/00-intro.md)
- [PHP-CLI](http://www.php-cli.com/)
- [PHP-CURL](http://php.net/manual/en/curl.setup.php)

1. Clone the repository. If you plan on contributing to the project, create a fork and clone the fork instead.
  ```bash
  cd $HOME
  git clone https://github.com/pantheon-systems/cli.git pantheon-cli
  ```
Or replace $HOME with the directory to which you would like to install.

2. Install the Composer dependencies.
  ```bash
  cd pantheon-cli
  composer install
  ```
You can now run the bleeding-edge version of Terminus via:
  ```bash
  bin/terminus
  ```

**Optionally**, for ease of development, we suggest aliasing or setting the path to this script in your Bash configuration file. This file is located at ~/.bashrc on Linux and ~/.bash_profile on Mac.
```bash
alias terminus="$HOME/pantheon-cli/bin/terminus"
```
or
```bash
export PATH="$PATH:$HOME/pantheon-cli/bin"
```
Once you source the file or restart your terminal client, you can now make use of Terminus using
```bash
terminus
```

Support
------------
Please make ready the steps to reproduce the issue, outputs, pertinent information about your system, and what you believe the correct reaction of the system ought to be. Reporting of issues encountered should happen in one of two ways:

**If yours is a problem with Terminus itself:**

1. Search [Terminus' issues on GitHub](https://github.com/pantheon-systems/cli/issues) to see whether another user has reported the same issue you are experiencing.
2. If the problem you are experiencing is not in the issues, you can open a new issue. Please include the helpful information you have gathered.
3. If you find your problem in an issue, feel free to add your issue information in the comments and/or subscribe to the issue's notifications.

**If the problem is with the Pantheon platform:**

Head over to [your support tickets in the Pantheon Dashboard](https://dashboard.pantheon.io/users/#support) and submit a new issue ticket. Please include the helpful information you have gathered.

Contributions
------------
Here are steps to follow if you would like to contribute to Terminus:

1. Fork the repository.
2. Add your changes. Please add tests as necessary. You can check your syntax for coherence to our standards by running
  ```bash
  cd $HOME/pantheon-cli
  ./scripts/lint.sh
  ```
And run your tests via:
  ```bash
  cd $HOME/pantheon-cli
  ./scripts/tests.sh
  ```

3. Open a pull request on GitHub so that we may evaluate and merge your changes.

Credits
------------
* We have leaned heavily on the work of [wp-cli](http://wp-cli.org/) in architecting this command line utility with an object-oriented structure.
* We've also (obviously) been greatly inspired by [Drush](http://drush.ws/).

Further Reading
------------
* [Terminus' Wiki](https://github.com/pantheon-systems/cli/wiki)
* [Usage](https://github.com/pantheon-systems/cli/wiki/Usage)
