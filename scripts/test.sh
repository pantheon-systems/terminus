#!/bin/bash

set -ex

# Basic lint test
#find . -type f -name "*.php" -exec php -l {} \;
echo $TRAVIS_COMMIT
for file in $( git diff --name-only $TRAVIS_COMMIT | grep ".php" ); do php -l $file; done
# Run the unit tests
# phpunit

# Run the functional tests
php behat.phar --format pretty


