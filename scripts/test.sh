#!/bin/bash

set -ex

# Basic lint test
for f in $( git diff-tree $TRAVIS_COMMIT --name-status -r | grep php | grep -v "^D" | awk '{print $2}') ; do php -l $f ; done

# Run the unit tests
# phpunit
export USE_FIXTURES=1;
export CLI_TEST_MODE=1;
phpunit

# Run the functional tests
# php behat.phar --format pretty
