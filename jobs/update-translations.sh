#!/bin/sh

set -e

vendor/bin/wp i18n make-pot . \
  --include=$( jobs/.files.sh | sed -e 's/ /,/g' ) \
  --headers='{"POT-Creation-Date":"2023-12-18T16:08:30+01:00"}' \
  --file-comment='Copyright (C) 2017 Webikon (Matej Kravjar)\nThis file is distributed under the GPLv2+.'

vendor/bin/wp i18n update-po languages/*.pot