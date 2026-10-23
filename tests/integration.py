#!/usr/bin/env -S python3 -I
# Integration test: a running Xibo CMS (tests/compose.yaml, started by tests/run.sh integration) with
# generated/es.mo and es.json mounted as README.md says. Without logging in, it checks that:
#   - the login page, asked for in Spanish, shows every text of its i18n block as generated/es.mo translates
#     the English one (the page Xibo renders with gettext, in PHP);
#   - at least one of them is a translation of es.po that Xibo's own es.mo does not have, so the mounted file
#     is the one in use and not the image's;
#   - /app/locale/langs/es.json (the i18next catalogue of the React interface) is generated/es.json, byte by byte.
#
# Usage: tests/integration.py <base URL>   (e.g. http://127.0.0.1:8099)

import gettext
import json
import sys
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
MARKER = "window.__LOGIN_CONFIG__ = "


def get(url, language=None):
    req = urllib.request.Request(url, headers={"Accept-Language": language} if language else {})
    with urllib.request.urlopen(req, timeout=30) as r:
        return r.read()


def login_texts(base, language):
    """The i18n block of the login page (window.__LOGIN_CONFIG__), as Xibo renders it for that language."""
    page = get(f"{base}/login", language).decode()
    start = page.find(MARKER)
    if start < 0:
        sys.exit(f"FAIL: no {MARKER.strip()} in /login ({language}). First bytes:\n{page[:300]}")
    config, _ = json.JSONDecoder().raw_decode(page, start + len(MARKER))
    return config["i18n"]


def main(base):
    failed = []
    mo = gettext.GNUTranslations(open(ROOT / "generated/es.mo", "rb"))
    xibo = json.loads((ROOT / "es.upstream.json").read_text())

    english = login_texts(base, "en")
    spanish = login_texts(base, "es")
    ours = 0
    for key, text in english.items():
        expected = mo.gettext(text)
        if spanish.get(key) != expected:
            failed.append(f"/login {key}: {spanish.get(key)!r}, expected {expected!r} (English {text!r})")
        elif expected != text and xibo.get(text) != expected:
            ours += 1
    if not ours:
        failed.append("/login: no text differs from Xibo's own translation, so the mounted es.mo is not shown in use")
    print(f"/login: {len(english)} texts in Spanish as es.mo translates them, {ours} of them not in Xibo's own es.mo.")

    served = get(f"{base}/app/locale/langs/es.json")
    if served != (ROOT / "generated/es.json").read_bytes():
        failed.append("/app/locale/langs/es.json is not generated/es.json")
    else:
        print(f"/app/locale/langs/es.json is generated/es.json ({len(served)} bytes).")

    for line in failed:
        print(f"FAIL: {line}")
    return 1 if failed else 0


if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit("Usage: tests/integration.py <base URL>")
    sys.exit(main(sys.argv[1].rstrip("/")))
