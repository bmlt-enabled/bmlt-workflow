#!/bin/sh

BRANCH=$1
if [ -z "$BRANCH" ]
then
    echo "$0 <branch name>"
    exit 1
fi

git branch -c "$BRANCH"
git switch $BRANCH
git push --set-upstream origin "$BRANCH"
