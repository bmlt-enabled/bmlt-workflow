#!/bin/bash -x
if [ $# -eq 0 ]
then
    echo "$0 <branch or tag>"
    exit 0
fi
cd releases
rm bmlt-workflow.zip
curl -o bmlt-workflow.zip -L https://github.com/bmlt-enabled/bmlt-workflow/archive/refs/heads/$1.zip

