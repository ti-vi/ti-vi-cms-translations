# Xibo CMS — Spanish (Latin America) translation

A complete Spanish translation of the [Xibo CMS](https://github.com/xibosignage/xibo-cms) web interface: every string of Xibo's `locale/default.pot`, reviewed by hand. It replaces Xibo's own Spanish files without modifying the image or the container: two files are mounted, read-only, over the ones of the image.

- **Latin American Spanish**, addressing the user as *usted*, with one glossary for the whole interface: *Layout* is «diapositiva», *Display* «pantalla», *Schedule* «programación», *Player* stays «Player». The full glossary and criteria are in [docs/traducir.md](docs/traducir.md) (in Spanish).
- Compared with Xibo's own Spanish translation (Xibo 4.5.5): about 2250 strings that Xibo leaves in English, and about 2400 corrections (wrong terms or meaning, broken placeholders, mixed *tú*/*usted*, some strings in Portuguese).
- **Each release is for exactly one Xibo version.** The files are Xibo's own catalogues with every string of [`es.po`](es.po) written over them, so they also carry the strings of that version that this catalogue does not change.

## Install (Docker)

For a standard Docker installation of Xibo (the `docker-compose.yml` of [xibo-docker](https://github.com/xibosignage/xibo-docker), service `cms-web`), in the folder of `docker-compose.yml`:

1. Download the release of your Xibo version, from [Releases](https://github.com/ti-vi/ti-vi-cms-translations/releases) (the tag is `<Xibo version>-<revision>`, take the highest revision):
   ```bash
   RELEASE=4.5.5-1
   mkdir -p translations
   for f in es.mo es.json VERSION SHA256SUMS; do
       curl -fsSL -o "translations/$f" "https://github.com/ti-vi/ti-vi-cms-translations/releases/download/$RELEASE/$f"
   done
   (cd translations && sha256sum -c SHA256SUMS && cat VERSION)   # must print your Xibo version
   ```
2. Mount them in `cms-web` with a `docker-compose.override.yml` next to `docker-compose.yml` (Docker Compose reads it automatically, and it survives Xibo upgrades):
   ```yaml
   services:
       cms-web:
           volumes:
               - "./translations/es.mo:/var/www/cms/locale/es.mo:ro"
               - "./translations/es.json:/var/www/cms/web/app/locale/langs/es.json:ro"
   ```
   On a host with SELinux, use `:ro,z`.
3. Recreate `cms-web` (the CMS is down for a few seconds):
   ```bash
   docker compose up -d
   ```

Xibo shows Spanish to a user whose preference is Spanish or, without a preference, whose browser asks for Spanish (*Administration → Settings*: language detection and default language). Then reload the page. Through a cache in front of Xibo (a reverse proxy, a CDN), `/app/locale/langs/es.json` may need purging.

**A new release**, or **before upgrading Xibo**: download the release of the target version into `translations/` the same way, then `docker compose up -d --force-recreate cms-web`. A mounted file keeps its old content when it is replaced, so `cms-web` has to be recreated. Files of another Xibo version do not break anything: the strings that differ between both versions show in English.

**Remove it**: delete the two lines from `docker-compose.override.yml` and run `docker compose up -d --force-recreate cms-web`.

**Without Docker**: copy `es.mo` to `<cms>/locale/es.mo` and `es.json` to `<cms>/web/app/locale/langs/es.json` (keep Xibo's own files to go back).

Some texts do not go through Xibo's catalogues and stay in English: report titles, dates, data of the database (user groups, tasks…), and the names of connectors and modules ([docs/traducir.md](docs/traducir.md#lo-que-no-se-puede-traducir-así)).

## Files of a release

| File | What it is |
|---|---|
| `es.mo` | gettext catalogue of the PHP and Twig pages (login, editors, server messages, e-mails). Mounted at `/var/www/cms/locale/es.mo`. |
| `es.json` | i18next catalogue of the React interface. Mounted at `/var/www/cms/web/app/locale/langs/es.json`. |
| `VERSION` | The Xibo version the files were built for. |
| `SHA256SUMS` | Checksums of the three files. |

A release is never replaced once published: a fix is a new revision (`4.5.5-2`), so its checksums can be pinned.

## Build and test

The catalogue is [`es.po`](es.po), a standard gettext file (Poedit, `msgfmt`). Building and testing only need Docker (and Python 3 for the integration test):

```bash
./build.sh               # builds generated/ (es.mo, es.json, VERSION) for the Xibo version of XIBO_VERSION
./build.sh --normalize   # rewrites es.po in the canonical format of GNU gettext, after editing it
tests/run.sh             # the tests of GitHub Actions: es.po, unit tests, build, and a throwaway Xibo with the files mounted
```

GitHub Actions runs the tests on every push and pull request ([`ci.yml`](.github/workflows/ci.yml)). A tag `<Xibo version>-<revision>` publishes a release with the files those tests passed with ([`release.yml`](.github/workflows/release.yml)).

How to change a string, the criteria and glossary, and how to move to a new Xibo version: [docs/traducir.md](docs/traducir.md) (in Spanish).

## License

[BSD 3-Clause](LICENSE). The release files include Xibo's own Spanish translations, which Launchpad publishes under the BSD license.
