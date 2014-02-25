#!/bin/bash

# called by Travis CI

if [[ "false" != "$TRAVIS_PULL_REQUEST" ]]; then
  echo "Not deploying pull requests."
  exit
fi

if [[ "$TRAVIS_BRANCH" != "$PACKAGE_BRANCH" ]]; then
  echo "Not on the '$PACKAGE_BRANCH' branch."
  exit
fi

# extract private key from decrypted environment variables stored in .travis.yml
echo -n $id_rsa_{00..30} >> ~/.ssh/id_rsa_base64
base64 --decode --ignore-garbage ~/.ssh/id_rsa_base64 > ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

# anyone can read the build log, so it MUST NOT contain any sensitive data
set -x

# add github's public key
echo "|1|qPmmP7LVZ7Qbpk7AylmkfR0FApQ=|WUy1WS3F4qcr3R5Sc728778goPw= ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAq2A7hRGmdnm9tUDbO9IDSwBK6TbQa+PXYPCPy6rbTrTtw7PHkccKrpp0yVhp5HdEIcKr6pLlVDBfOLX9QUsyCOV0wzfjIJNlGEYsdlLJizHhbn2mUjvSAHQqZETYP81eFzLQNnPHt4EVVUh7VfDESU84KezmD5QlWpXLmvU31/yMf+Se8xhHTvKSCZIFImWwoG6mbUoWf9nzpIoaSjB+weqqUUmpaaasXVal72J+UX2B+2RPW3RcT0eOzQgqlJL3RKrTJvdsjE3JEAvGq3lGHSZXy28G3skua2SmVi/w4yCE6gbODqnTWlg7+wC604ydGXA8VJiS5ap43JXiUFFAaQ==" >> ~/.ssh/known_hosts

git clone git@github.com:bensheldon/terminus-builds.git terminus-builds
cd terminus-builds

git config user.name "Travis CI"
git config user.email "travis@travis-ci.org"
git config push.default "current"

mv $TERMINUS_BIN_DIR/terminus phar/terminus-nightly.phar
chmod -x phar/terminus-nightly.phar

md5sum phar/terminus-nightly.phar | awk '{print $1}' > phar/terminus-nightly.phar.md5

git add phar/terminus-nightly.phar
git add phar/terminus-nightly.phar.md5
git commit -m "phar build: $TRAVIS_REPO_SLUG@$TRAVIS_COMMIT"

git push
