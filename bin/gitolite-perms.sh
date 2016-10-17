#!/bin/bash
#
# Get list of available repositories for GL_USER.
#
# !! security warning !!
# 1) set owner:group to GITOLITE_USER:GITOLITE_GROUP
#    !! it is important that HTTPD_USER is not owner of this script !!
# 2) set permissions to 0700
#    !! it is important that HTTPD_USER doesn't have ANY permissions !!

export GITOLITE_HOME="/home/git" # path to gitolite's home directory

${GITOLITE_HOME}/bin/gitolite info -json