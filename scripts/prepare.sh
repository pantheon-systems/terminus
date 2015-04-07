#!/bin/bash

# called by Travis CI

set -ex

composer install --no-interaction --prefer-source
