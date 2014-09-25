#!/bin/bash

set -ex

compgen -v

# Basic lint test
find . -type f -name "*.php" -exec php -l {} \;

# Run the unit tests
# phpunit

# Run the functional tests
php behat.phar --format pretty


