#!/bin/env sh

set -ex

echo "init shared"

rm -rf /run/shared/* || true
chmod -R 777 /run/shared


tail -f /dev/null