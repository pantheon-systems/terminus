#!/bin/bash

set -e

echo "Installing composer dependencies with --no-dev..."
composer install --no-dev

BUILD_VERSION=$(cat .version)
## if the github action doesn't explicitly set this var
## make sure this is tagged as a development build
if [[ -z "${BUILD_RELEASE}" ]]
then
  bumpversion release --allow-dirty --tag --commit patch
  BUILD_VERSION=$(cat .version)
fi



echo "Building terminus.phar...${BUILD_VERSION}"
box compile
echo "terminus.phar file has been created successfully!"

if [[ -n "${TERMINUS_ON_PHAR_COMPLETE_REINSTALL_COMPOSER_WITH_DEV}" ]]; then
    echo "Reinstalling composer dependencies with --dev..."
    composer install --dev
fi
