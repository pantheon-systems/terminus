#!/bin/bash

set -ex

# Basic lint test
for f in $( git diff-tree $TRAVIS_COMMIT --name-only -r ) ; do php -l $f ; done

# Run the unit tests
# phpunit

# Run the functional tests
php behat.phar --format pretty
