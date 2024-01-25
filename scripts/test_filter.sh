#!/bin/bash

# Set to output all run commands and exit on error
set -ex

FILTER=$1

./vendor/bin/phpunit --filter $FILTER
