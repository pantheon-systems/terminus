Pantheon CLI
============

[![Build Status](https://travis-ci.org/pantheon-systems/cli.svg?branch=master)](https://travis-ci.org/pantheon-systems/cli) [![Dependency Status](https://gemnasium.com/pantheon-systems/cli.svg)](https://gemnasium.com/pantheon-systems/cli)

The Pantheon Command Line Interface is a successor to the "Terminus" project, which provides access to the Pantheon Platform via Drush. While Terminus has provided great value (and we're going to keep the name in the product), we felt that it was important to have a first-class standalone CLI toolkit:

- That we could distribute as an independent executable.
- Which could "wrap" other commands that run on the platform.

If you would like to contribute, pull requests are welcome!

Important articles on the wiki to read
------------

[Installation](https://github.com/pantheon-systems/cli/wiki/Installation)
[Usage](https://github.com/pantheon-systems/cli/wiki/Usage)

Credits
-------
We have leaned heavily on the work of [wp-cli](http://wp-cli.org/) in architecting this command line utility with an object-oriented structure. We've also (obviously) been greatly inspired by [Drush](http://drush.ws/).
