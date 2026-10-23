#!/usr/bin/env bash
set -euo pipefail

# The tests of the repository: the same steps GitHub Actions runs (.github/workflows/ci.yml). They only need
# Docker: everything runs in throwaway containers. They check the catalogue and the build, not a running Xibo.
#
# Usage: tests/run.sh [po | unit | build]...   (no argument: all of them, in this order)
#   po           es.po is in the canonical format of GNU gettext (msgcat), valid (msgfmt --check) and complete
#                against the default.pot of the Xibo image (msgcmp)
#   unit         tests/unit.php, with the PHP of the Xibo image
#   build        ./build.sh, and es.upstream.json unchanged by it (it is committed after building)

cd "$(dirname "$0")/.."

VERSION=$(<XIBO_VERSION)
IMAGE="ghcr.io/xibosignage/xibo-cms:release-$VERSION"

step() { echo -e "\n== $*"; }

image() { docker image inspect "$IMAGE" >/dev/null 2>&1 || docker pull -q "$IMAGE" >/dev/null; }

test_po() {
    step "es.po against the default.pot of Xibo $VERSION (GNU gettext)"
    image
    # default.pot arrives on the standard input of msgcmp: that is why apt-get reads from /dev/null
    docker run --rm --network none --entrypoint cat "$IMAGE" /var/www/cms/locale/default.pot |
        docker run --rm -i -v "$PWD:/t:ro,z" debian:13-slim sh -c '
            apt-get -qq update && DEBIAN_FRONTEND=noninteractive apt-get -qq install -y gettext </dev/null >/dev/null &&
            { msgcat --sort-output /t/es.po | cmp -s - /t/es.po ||
                { echo "es.po is not in the canonical format of GNU gettext: run ./build.sh --normalize"; exit 1; }; } &&
            msgfmt --check --statistics -o /dev/null /t/es.po &&
            msgcmp /t/es.po - && echo "es.po: canonical, valid and complete."'
}

test_unit() {
    step "Unit tests (tests/unit.php)"
    image
    docker run --rm --network none --user "$(id -u):$(id -g)" --entrypoint php -v "$PWD:/t:ro,z" "$IMAGE" /t/tests/unit.php
}

test_build() {
    step "Build (./build.sh)"
    local before
    before=$(sha256sum es.upstream.json 2>/dev/null || true)
    ./build.sh
    if [[ "$(sha256sum es.upstream.json)" != "$before" ]]; then
        echo "FAIL: the build changed es.upstream.json: build with ./build.sh and commit it with es.po."
        return 1
    fi
}

tests=("$@")
(( ${#tests[@]} )) || tests=(po unit build)
for t in "${tests[@]}"; do
    case "$t" in
        po|unit|build) "test_$t" ;;
        *) echo "Usage: tests/run.sh [po | unit | build]..." >&2; exit 1 ;;
    esac
done
echo -e "\nAll passed: ${tests[*]}."
