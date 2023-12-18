#!/bin/sh

if [ "$1" = "--fix" ]
then
  command=phpcbf
else
  command="phpcs -s"
fi

jobs/.runner.sh vendor/bin/$command -v --
