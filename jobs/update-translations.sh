#!/bin/sh

set -e

vendor/bin/wp i18n make-pot . \
  --include=$( jobs/.files.sh | sed -e 's/ /,/g' )

vendor/bin/wp i18n update-po languages/*.pot