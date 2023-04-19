#!/bin/sh

# Copyright (C) 2023 nigel.bmlt@gmail.com
# 
# This file is part of bmlt-workflow.
# 
# bmlt-workflow is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# bmlt-workflow is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.


BRANCH=$1
RELEASE=$2

if [ -z "$BRANCH" -o -z "$RELEASE" ]
then
    echo "$0 <branch name> <release number>"
    exit 1
fi

if [ ! -f config.php ] && [ ! -f bmlt-workflow.php ]
then
    echo config.php and bmlt-workflow.php not found
    exit 1
fi

git switch $BRANCH
if [ $? != 0 ]
then
    echo "Error locating source branch $BRANCH"
    exit 1
fi

BV=$(grep " * Version:" bmlt-workflow.php | awk '{print $3}')
PV=$(grep "define('BMLTWF_PLUGIN_VERSION" bmlt-workflow.php | awk -F \' '{print $4}')
RV=$(grep "Stable tag:" readme.txt | awk '{print $3}')

if [ ! $PV == $RELEASE ]
then
    echo "BMLTWF_PLUGIN_VERSION version $PV not equal to release version $RELEASE"
    exit 1
fi

if [ ! $BV == $RELEASE ]
then
    echo "BMLT Workflow header version $BV not equal to release version $RELEASE"
    exit 1
fi

if [ ! $RV == $RELEASE ]
then
    echo "Readme version $RV not equal to release version $RELEASE"
    exit 1
fi

sed -i'.bak' "s/define('BMLTWF_DEBUG', true);/define('BMLTWF_DEBUG', false);/g" ./config.php
rm config.php.bak

wp i18n make-json lang --no-purge
if [ $? != 0 ]
then
    echo "i18n creation failed"
    exit 1
fi
git add lang/*
git commit -m "Update lang files"
git push

export DOIT='n'
echo "Are you ok to merge $BRANCH into main as release $RELEASE? [yN]"
read DOIT

if [ a${DOIT}a != "aa" ] && [ $DOIT == 'y' ]
then
    git switch main
    if [ $? != 0 ]
    then
        exit 1
    fi

    git merge --squash "$BRANCH"
    if [ $? != 0 ]
    then
        exit 1
    fi

    git commit -m "$RELEASE"
    if [ $? != 0 ]
    then
        exit 1
    fi

    git push
    if [ $? != 0 ]
    then
        exit 1
    fi
    
    git tag "$RELEASE"
    git push --tag
else
    exit 1
fi