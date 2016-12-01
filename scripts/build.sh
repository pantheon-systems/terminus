#!/bin/bash

set -ex

CREATED_PHAR='terminus.phar'
BUILD_DIR="$PWD/builds"
DESTINATION="$BUILD_DIR/$CREATED_PHAR"

if ! type 'composer' > /dev/null; then
    echo 'You need to install Composer before you can build using this script.'
    exit
fi

# Remove dev packages for massive PHAR size reduction
composer install --no-dev

# Ensure the destination directory exists
mkdir -p $BUILD_DIR

# Make the PHAR file
php -dphar.readonly=0 utils/make-phar.php $CREATED_PHAR $*

# Move the PHAR file to the builds directory
mv -f $CREATED_PHAR $DESTINATION

# Set the file permission for the new PHAR file
chmod +x $DESTINATION

# Restore the dev packages
composer install

echo "Created PHAR file at $DESTINATION"
