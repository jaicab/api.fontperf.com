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

git remote add upstream "https://${GITHUB_TOKEN}@github.com/jaicab/api.fontperf.com.git"
git fetch upstream
git reset upstream/production

touch .

git add -A .
git reset ".travis.yml"
git reset ".gitignore"
git reset "README.md"
git reset "LICENSE"
git reset "phpunit.xml"
git reset "composer.json"
git reset "composer.lock"
git reset "script/deploy.sh"
git commit -m "Launch ${rev} to production"
git push -q upstream HEAD:production