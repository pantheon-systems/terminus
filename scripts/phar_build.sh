#!/bin/bash

set -e

echo "Installing composer dependencies with --no-dev..."
composer install --no-dev

echo "Building terminus.phar..."
box compile
echo "terminus.phar file has been created successfully!"

if [[ ! -z "${TERMINUS_ON_PHAR_COMPLETE_REINSTALL_COMPOSER_WITH_DEV}" ]]; then
    echo "Reinstalling composer dependencies with --dev..."
    composer install --dev
fi
