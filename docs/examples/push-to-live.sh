#!/bin/bash
site=${1:?"Sitename required"}

if [[ $site != $( basename $PWD ) ]]; then
	echo 'Please navigate to the site directory before executing'
	exit 1
fi

if [ ! -d ./code ]; then
	echo 'Cannot find ./code directory'
	exit 1
fi

LOCAL_DIR=$PWD

cd code
branch=$(git branch | grep \* | awk '{print $2}')
if [[ 0 == $( git diff --stat origin/master | wc -l ) ]] ; then
	echo 'You do not appear to have any changes to push ... skipping '
else
	git push origin $branch
fi

terminus site deploy --site=$site --env=test

url="test-$site.pantheon.io"
status=$( curl -sI $url | grep HTTP | awk '{print $2}' )
if [[ $status != "200" ]]; then
	echo "Deployed to test but the test site seems to have a problem"
	curl -sI --cookie "NO_CACHE=1" $url
	exit 1
fi

echo "Received a 200 response code from test site. "
echo -n "Would you like to go ahead and deploy to live? (y/n):"
read live

if [[ "y" == "$live" ]]; then
	terminus site deploy --site=$site --env=live
fi
