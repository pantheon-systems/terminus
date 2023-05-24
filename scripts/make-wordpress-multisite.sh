#!/usr/bin/env bash

set -e

TERMINUS_SITE_WP_NETWORK_UPSTREAM="word_press_multisite"
TERMINUS_SITE_WP_NETWORK_TITLE="TERMINUS-TEST-SITE"
TERMINUS_SITE_WP_NETWORK_ADMIN_USER="admin"
TERMINUS_SITE_WP_NETWORK_ADMIN_EMAIL="admin@pantheon.io"
LOCAL_CLONED_DIRECTORY="${HOME}/pantheon-local-copies/${TERMINUS_SITE_WP_NETWORK}"

if test -z "${TERMINUS_SITE_WP_NETWORK}"
then
  echo "Please set the following environment variables:"
  echo "TERMINUS_SITE_WP_NETWORK"
  echo "TERMINUS_SITE_WP_NETWORK_LABEL"
  echo "TERMINUS_ORG"
  exit 1
fi

if terminus site:info ${TERMINUS_SITE_WP_NETWORK} --format=json | jq -r .id
then
  echo "${TERMINUS_SITE_WP_NETWORK} exists... skipping creation..."
else
  echo "${TERMINUS_SITE_WP_NETWORK} doesn't exist... creating..."
  terminus site:create \
    "${TERMINUS_SITE_WP_NETWORK}" \
    "${TERMINUS_SITE_WP_NETWORK}" \
    "${TERMINUS_SITE_WP_NETWORK_UPSTREAM}" \
    --org="${TERMINUS_ORG}"
fi

if [ ! -d "${LOCAL_CLONED_DIRECTORY}" ]
then
  echo "Cloning ${TERMINUS_SITE_WP_NETWORK}"
  terminus local:clone \
    "${TERMINUS_SITE_WP_NETWORK}"
fi

terminus wp ${TERMINUS_SITE_WP_NETWORK}.dev -- core install \
  --title="${TERMINUS_SITE_WP_NETWORK_TITLE}" \
  --admin_user="${TERMINUS_SITE_WP_NETWORK_ADMIN_USER}" \
  --admin_email="${TERMINUS_SITE_WP_NETWORK_ADMIN_EMAIL}"

terminus wp ${TERMINUS_SITE_WP_NETWORK}.dev -- core multisite-install \
  --title="${TERMINUS_SITE_WP_NETWORK_TITLE}" \
  --admin_user="${TERMINUS_SITE_WP_NETWORK_ADMIN_USER}" \
  --admin_email="${TERMINUS_SITE_WP_NETWORK_ADMIN_EMAIL}"

terminus wp ${TERMINUS_SITE_WP_NETWORK}.dev -- plugin install \
  pantheon-advanced-page-cache \
  --activate

terminus env:commit \
  "${TERMINUS_SITE_WP_NETWORK}.dev" \
  --message="Initial commit"

terminus connection:set \
  "${TERMINUS_SITE_WP_NETWORK}.dev" \
  git

cd "${LOCAL_CLONED_DIRECTORY}" && git pull
