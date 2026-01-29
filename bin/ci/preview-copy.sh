#!/usr/bin/env bash
set -euo pipefail
if [[ "${DEBUG:-0}" == "1" ]]; then
  set -x
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

rm -rf "$ROOT_DIR/deploy"
mkdir -p "$ROOT_DIR/deploy/var/cache"
mkdir -p "$ROOT_DIR/deploy/src"
cp -a public vendor config "$ROOT_DIR/deploy/"
cp -a src/http src/resources "$ROOT_DIR/deploy/src/"
cp -a var/cache/html "$ROOT_DIR/deploy/var/cache/"
