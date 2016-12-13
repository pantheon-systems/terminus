#!/bin/bash

# Set to output all run commands and exit on error
set -ex

# Check the code's syntax
find src/* tests/* utils/* bin/* -type f -name "*.php" -exec php -l {} \; | grep "No syntax errors"

# Lint the code
vendor/bin/phpcs --standard=PSR2 --extensions=php --severity=1 -n tests/* bin/terminus src/*

# Run the unit tests
vendor/bin/phpunit --colors=always -c tests/config/phpunit.xml.dist --debug

# Run the functional tests
if [ ! -z $1 ]; then
  vendor/bin/behat --colors -c=tests/config/behat.yml --suite=$1
else
  vendor/bin/behat --colors -c=tests/config/behat.yml --suite=default
fi
