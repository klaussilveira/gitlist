#!/bin/bash
#
# Wrapper for gitolite calls.
#
# !! security warning !!
# 1) allow HTTPD_USER to be able to run ONLY DOCUMENT_ROOT/bin/gitolite-perms.sh via sudo
# 2) set owner:group to GITOLITE_USER:HTTPD_USER_GROUP
#    !! it is important that HTTPD_USER is not owner of this script !!
# 3) set permissions to 0750
#    !! it is important that HTTPD_USER doesn't have write permissions !!

if [[ $# -ne 1 ]]; then
    >&2 echo "You have to pass username as argument."
    exit 1
fi

GITOLITE_USER="git" # user which gitolite is running under
GITOLITE_PERMS_WRAPPER="/var/www/gitlist/bin/gitolite-perms.sh" # path to DOCUMENT_ROOT/bin/gitolite-perms.sh

sudo -u ${GITOLITE_USER} GL_USER="$1" ${GITOLITE_PERMS_WRAPPER}