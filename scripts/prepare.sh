#!/bin/bash

# called by Travis CI

set -ex

composer install --no-interaction --prefer-source
test -d fixtures ||  mkdir -p fixtures
curl http://behat.org/downloads/behat.phar > behat.phar
