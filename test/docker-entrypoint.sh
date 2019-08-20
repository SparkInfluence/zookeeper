#!/bin/bash

set -m

zkServer.sh start > /dev/null 2>&1

if ! zkServer.sh status > /dev/null 2>&1 ; then
  echo "Zookeeper failed to start!" 1>&2
  exit 1
fi

exitcode=2
if vendor/bin/phpunit "$@"; then
  exitcode=0
fi

zkServer.sh stop > /dev/null 2>&1
exit $exitcode
