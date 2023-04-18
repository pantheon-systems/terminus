#!/bin/bash

set -e

echo "Installing composer dependencies with --no-dev..."
composer install --no-dev

BUILD_VERSION=$(cat .version)
## if the github action doesn't explicitly set this var
## make sure this is tagged as a development build
if [[ -z "${BUILD_RELEASE}" ]]
then
  ## When you build, you're always building (.version + 1-dev)
  ## unless you build a release, in which case you're building (.version)
  VERSION=$(cat .version)
  BUILD_VERSION="${VERSION}-dev"
fi



echo "Building terminus.phar...${BUILD_VERSION}"
box compile
echo "terminus.phar file has been created successfully!"

if [[ -n "${TERMINUS_ON_PHAR_COMPLETE_REINSTALL_COMPOSER_WITH_DEV}" ]]; then
    echo "Reinstalling composer dependencies with --dev..."
    composer install --dev
fi
