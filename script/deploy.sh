#!/bin/bash

set -o errexit -o nounset

if [ "$TRAVIS_PULL_REQUEST" != "false" -o "$TRAVIS_BRANCH" != "master" ]
then
  echo "This commit was made against the $TRAVIS_BRANCH and not the master! No deploy!"
  exit 0
fi

rev=$(git rev-parse --short HEAD)

git init
git config user.name "Travis CI"
git config user.email "nobody@fontperf.com"

git remote add upstream "https://$GH_TOKEN@github.com/jaicab/api.fontperf.com.git"
git fetch upstream
git reset upstream/production

touch .

git add -A .
git commit -m "Launch ${rev} to production"
git push -q upstream HEAD:production