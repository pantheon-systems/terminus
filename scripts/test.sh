#!/bin/bash

set -ex

# Basic lint test
if [[ ! $TRAVIS_COMMIT ]]; then
	TRAVIS_COMMIT=$( git log --format=oneline | head -n1 | awk '{print $1}' )
fi

for f in $( git diff-tree $TRAVIS_COMMIT --name-status -r | grep php | grep -v "^D" | awk '{print $2}') ; do php -l $f ; done

# Run the unit tests
# phpunit
vendor/bin/phpunit --debug

# Run the functional tests
vendor/bin/behat
