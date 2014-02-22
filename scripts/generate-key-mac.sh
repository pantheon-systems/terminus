#!/usr/bin/env bash

# On OS X, use this script to generate an encrypted deployment key for Travis CI.

# Dependencies:
# gem install travis
# brew install coreutils

# Also, you must enable travis for the repository that holds the builds

if [ $# -lt 1 ]; then
  echo "usage: $0 <user>/<repo>"
  exit 1
fi

REPO=$1

KEY_DIR=/tmp/terminus-deploy-key
mkdir -p $KEY_DIR

echo "Generating key pair..."
ssh-keygen -q -f $KEY_DIR/id_rsa -P ''

echo "Encrypting private key..."
base64 --break=0 $KEY_DIR/id_rsa > $KEY_DIR/id_rsa_base64
ENCRYPTION_FILTER="echo \$(echo \"- secure: \")\$(travis encrypt \"\$FILE='\`cat $FILE\`'\" -r $REPO)"
gsplit --bytes=100 --numeric-suffixes --suffix-length=2 --filter="$ENCRYPTION_FILTER" $KEY_DIR/id_rsa_base64 id_rsa_

echo
echo "1. Add the above lines to your .travis.yml file."
echo "2. Call read-key.sh from your .travis.yml file."
echo "3. Add $KEY_DIR/id_rsa.pub as a deploy key to the destination Github repo."


# To reconstitute the private SSH key from within the Travis-CI build (typically from 'before_script')
# echo -n $id_rsa_{00..30} >> ~/.ssh/id_rsa_base64
# base64 --decode --ignore-garbage ~/.ssh/id_rsa_base64 > ~/.ssh/id_rsa
# chmod 600 ~/.ssh/id_rsa
# echo -e "Host github.com\n\tStrictHostKeyChecking no\n" >> ~/.ssh/config
