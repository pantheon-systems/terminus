Contribute
==========

Creating Issues
---------------

Run `terminus self:info` to confirm you are [running the latest version](https://github.com/pantheon-systems/terminus/releases) before opening a new issue.

Setting Up
----------

Fork this repo then open a terminal session and execute the following:

```bash
$ cd /path/to/pantheon/projects (ex. ~/projects/pantheon)
$ git clone -b 3.x git@github.com:myorg/terminus.git (replace myorg with your forked GitHub account)
$ cd terminus
$ composer install
$ sudo ln -s $(pwd)/bin/t3 /usr/local/bin/t3 (replace /usr/local/bin with your preferred location, if desired)
$ t3 -V (should return the version)
```

Submitting Patches
------------------

Whether you want to fix a bug or implement a new feature, the process is pretty much the same:

0. [Search existing issues](https://github.com/pantheon-systems/terminus/issues); if you can't find anything related to what you want to work on, open a new issue so that you can get some initial feedback.
1. [Fork](https://github.com/pantheon-systems/terminus/fork) the repository.
2. Push the code changes from your local clone to your fork.
3. Open a pull request.

It doesn't matter if the code isn't perfect. The idea is to get it reviewed early and iterate on it.

If you're adding a new feature, please add one or more functional tests for it in the `tests/Functional/` directory. See below. Also, keep the documentation up-to-date by running:

  ```bash
  cd /install/location/terminus
  composer docs
  ```


Lastly, please follow [PSR-2](http://www.php-fig.org/psr/psr-2/).  You can test for conformance via:
  ```bash
  cd /install/location/terminus
  composer cs
  ```
The PHP code beautifier can automatically fix a number of style issues. Run it via:
  ```bash
  cd /install/location/terminus
  composer cbf
  ```

Running and Writing Tests
-------------------------

Terminus uses functional tests implemented using [PHPUnit](http://phpunit.de/)

The tests can be run via:

`composer test:functional`

### Functional Tests

The functional test files are in the `tests/Functional` directory.

The Terminus 3.x functional tests can be run via:

  ```bash
  cd /install/location/terminus
  composer test:functional
  ```


Versioning
----------

### Versions

In keeping with the standards of semantic versioning, backward-incompatible fixes are targeted to "major" versions. "Minor" versions are reserved for significant feature/bug releases needed between major versions. "Patch" releases are reserved only for critical security issues and other bugs critical to stabilizing the release.

After a new major version is released, previous major versions are actively supported for one year.

#### What qualifies as a backward-incompatible change?

Our initial commitment will be to command compatibility and parameter compatibility. However, since on the command line STDOUT and STDERR are essentially APIs we will make a best effort to keep machine-readable output compatibility, meaning if your code interfaces with Terminus via --format=json or --format=bash formatting, we will try our best to ensure these are stable and compatible between minor release. However, changes to the STDOUT, like success and fail messages, should not be assumed to be compatible.

### Release Stability

If you are using Terminus in a production environment, you should be deploying the executable for [the latest release](https://github.com/pantheon-systems/terminus/releases).

Ongoing development on the next planned release will be on the master branch and should not be considered stable, as changes will be taking place on a daily basis.

Feedback
--------

Writing for Terminus should be fun. If you find any of this hard to figure out, let us know so we can improve our process or documentation!
