#!/bin/bash

set -ex

if [[ ! $TRAVIS_COMMIT ]]; then
  TRAVIS_COMMIT=$( git log --format=oneline | head -n1 | awk '{print $1}' )
fi

for f in $( git diff-tree $TRAVIS_COMMIT --name-status -r | grep php | grep -v "^D" | awk '{print $2}') ; do php -l $f ; done

vendor/bin/phpcs --standard=PSR2 --extensions=php --severity=1 -n tests/* php/*
vendor/bin/phpunit --colors=always -c tests/config/phpunit.xml.dist --debug

# Run the functional tests
behat_cmd="vendor/bin/behat --colors -c=tests/config/behat.yml --suite="
if [ ! -z $1 ]; then
  behat_cmd+=$1
else
  behat_cmd+="default"
fi

eval $behat_cmd
