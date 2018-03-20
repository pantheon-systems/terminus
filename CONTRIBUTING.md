Contribute
==========

Creating Issues
---------------

Run `terminus self:info` to confirm you are [running the latest version](https://github.com/pantheon-systems/terminus/releases) before opening a new issue.

Setting Up
----------

1. Clone this Git repository on your local machine.
2. Install [Composer](https://getcomposer.org/) if you don't already have it.
3. Run `composer install` to fetch all the dependencies.
4. Run `./bin/terminus --help` to test that everything was installed properly.

Submitting Patches
------------------

Whether you want to fix a bug or implement a new feature, the process is pretty much the same:

0. [Search existing issues](https://github.com/pantheon-systems/terminus/issues); if you can't find anything related to what you want to work on, open a new issue so that you can get some initial feedback.
1. [Fork](https://github.com/pantheon-systems/terminus/fork) the repository.
2. Push the code changes from your local clone to your fork.
3. Open a pull request.

It doesn't matter if the code isn't perfect. The idea is to get it reviewed early and iterate on it.

If you're adding a new feature, please add one or more functional tests for it in the `tests/features/` directory. See below. Also, keep the documentation up-to-date by running:

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

There are two types of automated tests:

* unit tests, implemented using [PHPUnit](http://phpunit.de/)
* functional tests, implemented using [Behat](http://behat.org)

Both the unit and functional tests can be run together via:

`composer test`

### Unit Tests

The unit test files are in the `tests/unit-tests` directory.

To run the unit tests for Terminus 0.x, simply execute:

  `vendor/bin/phpunit`
  
The Terminus 1.x unit tests can be run via:

  ```bash
  cd /install/location/terminus
  composer phpunit
  ```


### Functional Tests

The functional test files are in the `tests/features` directory. Any test which touches the backed is mocked with [VCR](http://php-vcr.github.io).

#### Running existing tests

To run the entire test suite for Terminus 0.x:

  `vendor/bin/behat -c=tests/config/behat.yml`

Or to test a single feature:

  `vendor/bin/behat -c=tests/config/behat.yml tests/features/core.feature`

The functional test files for the new version of Terminus are in the `tests/active-features` directory. The complete behat suite for Terminus 1.x can be run via:

  ```bash
  cd /install/location/terminus
  composer behat
  ```

More information can be found by running `vendor/bin/behat --help`.

#### Recording new tests

To record a new test, configure the `parameters` section of the file [tests/config/behat.yml](tests/config/behat.yml) as follows:
```
parameters:
  user_id:                 '[[YOUR-USER-ID-HERE]]'
  username:                '[[YOUR-EMAIL-ADDRESS-HERE]]'
  host:                    'terminus.pantheon.io:443'
  vcr_mode:                'new_episodes'
  machine_token:           '[[YOUR-MACHINE-TOKEN-HERE]]'
```
Then, run a single test as described above. VCR will then call the backend and record the results received in the specified .yml file. This is done for any Behat scenario labeled `@vcr filename.yml`. Pick a filename appropriate for the test.

Once the VCR .yml file has been saved, you may restore your behat.yml configuration file to its previous state (at a minimum, set `vcr_mode` back to `none`). Subsequent test runs will pull data from the VCR .yml file to satisfy future web requests.

You may need to add yourself to the [team of the behat-tests site](https://admin.dashboard.pantheon.io/sites/e885f5fe-6644-4df6-a292-68b2b57c33ad#dev/code) (Pantheon employees) or use a different test site. Once you have captured the events you would like to record, hand-sanitize them of any sensitive information such as machine tokens and bearer authorization headers.

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
