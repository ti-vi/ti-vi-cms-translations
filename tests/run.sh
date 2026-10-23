#!/usr/bin/env bash
set -euo pipefail

# The tests of the repository: the same steps GitHub Actions runs (.github/workflows/ci.yml). They need Docker,
# and Python 3 for the integration test; everything else runs in throwaway containers.
#
# Usage: tests/run.sh [po | unit | build | integration]...   (no argument: all of them, in this order)
#   po           es.po is in the canonical format of GNU gettext (msgcat), valid (msgfmt --check) and complete
#                against the default.pot of the Xibo image (msgcmp)
#   unit         tests/unit.php, with the PHP of the Xibo image
#   build        ./build.sh, and es.upstream.json unchanged by it (it is committed after building)
#   integration  a throwaway Xibo with generated/ mounted as README.md says (tests/compose.yaml), checked by
#                tests/integration.py

cd "$(dirname "$0")/.."

VERSION=$(<XIBO_VERSION)
IMAGE="ghcr.io/xibosignage/xibo-cms:release-$VERSION"
PORT=${TEST_PORT:-8099}
COMPOSE=(docker compose -f tests/compose.yaml)
export XIBO_VERSION="$VERSION" TEST_PORT="$PORT"

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

test_integration() {
    step "Integration: Xibo $VERSION with generated/ mounted (tests/compose.yaml, port $PORT)"
    [[ -f generated/es.mo && -f generated/es.json ]] || { echo "FAIL: generated/ not built: run ./build.sh first."; return 1; }
    trap '"${COMPOSE[@]}" down -v >/dev/null 2>&1' EXIT
    "${COMPOSE[@]}" up -d --quiet-pull
    local elapsed=0
    until [[ "$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$PORT/login" || true)" == 200 ]]; do
        if (( elapsed >= 600 )); then
            "${COMPOSE[@]}" logs --tail 50 cms-web
            echo "FAIL: Xibo did not answer /login in 10 minutes (logs above)."
            return 1
        fi
        sleep 10
        elapsed=$(( elapsed + 10 ))
    done
    python3 -I tests/integration.py "http://127.0.0.1:$PORT" || { "${COMPOSE[@]}" logs --tail 30 cms-web; return 1; }
}

tests=("$@")
(( ${#tests[@]} )) || tests=(po unit build integration)
for t in "${tests[@]}"; do
    case "$t" in
        po|unit|build|integration) "test_$t" ;;
        *) echo "Usage: tests/run.sh [po | unit | build | integration]..." >&2; exit 1 ;;
    esac
done
echo -e "\nAll passed: ${tests[*]}."
