#!/usr/bin/env bash
set -euo pipefail

# Builds generated/ (es.mo, es.json and VERSION: the files mounted into Xibo, README.md) and es.upstream.json
# from es.po and the files of the Xibo image of XIBO_VERSION.
#
# It runs build.php in a throwaway container of that image (without network), so it reads the original files of
# the image with Xibo's own gettext library. Only Docker is needed. Procedure: docs/traducir.md.
#
# Usage:
#   ./build.sh                 # build generated/ and es.upstream.json, and report every string to review
#   ./build.sh --untranslated  # list the interface strings still untranslated, by interface file
#   ./build.sh --normalize     # rewrite es.po in the canonical format of GNU gettext (msgcat), after editing it

cd "$(dirname "$0")"

MOUNT=rw
SCRIPT=build.php
case "${1:-}" in
    "")              ;;
    --untranslated)  MOUNT=ro; SCRIPT=untranslated.php ;;
    --normalize)     SCRIPT="" ;;
    *)               echo "Usage: ./build.sh [--untranslated | --normalize]" >&2; exit 1 ;;
esac

# GNU gettext from a throwaway Debian container (the format of es.po is the one of its msgcat). Written by this
# shell, so es.po keeps its owner; nothing is written if msgcat fails.
if [[ -z "$SCRIPT" ]]; then
    if ! docker run --rm -v "$PWD:/t:ro,z" debian:13-slim sh -c '
        apt-get -qq update && DEBIAN_FRONTEND=noninteractive apt-get -qq install -y gettext </dev/null >/dev/null &&
        msgcat --sort-output /t/es.po' >es.po.tmp; then
        rm -f es.po.tmp
        exit 1
    fi
    mv es.po.tmp es.po
    echo "es.po normalized (git diff shows what changed)."
    exit 0
fi

VERSION=$(<XIBO_VERSION)
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Error: XIBO_VERSION is not a Xibo version (x.y.z)." >&2; exit 1; }
IMAGE="ghcr.io/xibosignage/xibo-cms:release-$VERSION"

docker image inspect "$IMAGE" >/dev/null 2>&1 || docker pull "$IMAGE"
exec docker run --rm --network none --user "$(id -u):$(id -g)" --entrypoint php \
    -v "$PWD:/translations:$MOUNT,z" "$IMAGE" "/translations/$SCRIPT" "$VERSION"
