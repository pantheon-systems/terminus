#!/bin/bash

set -ex

TERMINUS_BIN_DIR=${TERMINUS_BIN_DIR-builds}

# Regenerate the internal documentation
php utils/make-docs.php
# Install Composer nearby for use
curl -sS https://getcomposer.org/installer | php -- --filename=$TERMINUS_BIN_DIR/composer.phar
# Remove dev packages for massive PHAR size reduction
php $TERMINUS_BIN_DIR/composer.phar install --no-dev
# the Behat test suite will pick up the executable found in $TERMINUS_BIN_DIR
mkdir -p $TERMINUS_BIN_DIR
php -dphar.readonly=0 utils/make-phar.php terminus.phar --quiet
mv terminus.phar $TERMINUS_BIN_DIR/terminus.phar
cp $TERMINUS_BIN_DIR/terminus.phar $TERMINUS_BIN_DIR/terminus
chmod +x $TERMINUS_BIN_DIR/terminus.phar
chmod +x $TERMINUS_BIN_DIR/terminus
php $TERMINUS_BIN_DIR/composer.phar update
rm $TERMINUS_BIN_DIR/composer.phar
