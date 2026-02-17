#!/bin/sh

set -e

slug=$(echo *.php | rev | cut -d. -f2- | rev)

vendor/bin/wp i18n make-pot . languages/$slug.pot \
  --domain=$slug \
  --include=$( jobs/.files.sh | sed -e 's/ /,/g' ) \
  --exclude=vendor \
  --headers='{
    "POT-Creation-Date":"2023-12-18T16:08:30+01:00",
    "Report-Msgid-Bugs-To":"https://wordpress.org/support/plugin/wc-bacs-paybysquare/\n"
}' \
  --file-comment='Copyright (C) 2017 Webikon (Matej Kravjar)\nThis file is distributed under the GPLv2+.'

vendor/bin/wp i18n update-po languages/$slug.pot
