#!/bin/bash

# Set to output all run commands and exit on error
set -ex

# Check the code's syntax
find src/* tests/* utils/* bin/* -type f -name "*.php" -exec php -l {} \; | grep "No syntax errors"

# Lint the code
composer cs

# Run the unit tests
composer phpunit

# Run the functional tests
composer behat
