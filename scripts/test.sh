#!/bin/bash

set -ex

# Basic lint test
if [[ ! $TRAVIS_COMMIT ]]; then
  TRAVIS_COMMIT=$( git log --format=oneline | head -n1 | awk '{print $1}' )
fi

for f in $( git diff-tree $TRAVIS_COMMIT --name-status -r | grep php | grep -v "^D" | awk '{print $2}') ; do php -l $f ; done

# Run the unit tests
vendor/bin/phpunit --debug

# Run the functional tests
behat_cmd="vendor/bin/behat -c=tests/config/behat.yml"
behat_setup="./tests/config/behat_parameters.php"
if [ ! -z $1 ]; then
  behat_setup+=" $1"
  behat_cmd+=" --suite=$1"
fi
params=$(eval $behat_setup)
cmd="TEST_PARAMS='$params' $behat_cmd"
eval $cmd
