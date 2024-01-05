#!/bin/sh

find $( jobs/.files.sh ) -name '*.php' -print0 | xargs -0r $*
