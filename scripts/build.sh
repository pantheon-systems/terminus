#!/bin/bash

set -ex

TERMINUS_BIN_DIR=${TERMINUS_BIN_DIR-builds}

# the Behat test suite will pick up the executable found in $TERMINUS_BIN_DIR
mkdir -p $TERMINUS_BIN_DIR
php -dphar.readonly=0 utils/make-phar.php terminus.phar --quiet
mv terminus.phar $TERMINUS_BIN_DIR/terminus.phar
cp $TERMINUS_BIN_DIR/terminus.phar $TERMINUS_BIN_DIR/terminus
chmod +x $TERMINUS_BIN_DIR/terminus.phar
chmod +x $TERMINUS_BIN_DIR/terminus
