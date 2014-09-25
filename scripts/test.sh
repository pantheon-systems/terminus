#!/bin/bash

set -ex

# Basic lint test
for f in $( git diff-tree --n -commit-id --name-only -r $TRAVIS_COMMIT ) ; do php -l ; done

# Run the unit tests
# phpunit

# Run the functional tests
php behat.phar --format pretty


