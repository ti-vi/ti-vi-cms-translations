# AGENTS.md

Contexto para agentes (y personas) que trabajen en este repositorio. La instalación está en [README.md](README.md) (en inglés). El catálogo, sus criterios y cómo se cambia, en [docs/traducir.md](docs/traducir.md).

## Qué es

La traducción completa al español de Latinoamérica de la interfaz de Xibo CMS, para cualquier instalación de Xibo:

- **La fuente es `es.po`**, el catálogo entero: una entrada por cada `msgid` del `default.pot` de Xibo.
- **`./build.sh`** lo escribe sobre los catálogos de la imagen de Xibo de `XIBO_VERSION` y deja el resultado en `generated/` (`es.mo`, `es.json`, `VERSION`). Lo hace en un contenedor desechable de esa imagen, con la librería de gettext de Xibo.
- **GitHub Actions** prueba cada push y pull request y publica una release por cada tag `<versión de Xibo>-<revisión>`.
- **Quien usa una release** monta los dos ficheros en `cms-web`, de solo lectura, sin modificar la imagen.

El repo es **público**: ni comentarios, ni documentación, ni mensajes nombran a la empresa o a los despliegues de quien lo mantiene. Los ejemplos usan `cms.example.com`.

## Ficheros

| Fichero | Qué es |
|---|---|
| `es.po` | El catálogo. La única fuente que se edita. |
| `XIBO_VERSION` | La versión de Xibo para la que se construye. |
| `es.upstream.json` | La traducción de Xibo de cada cadena en la última build. Lo escribe `build.sh` y se versiona: el informe de la siguiente versión de Xibo se compara con él. |
| `build.sh`, `build.php`, `lib.php`, `untranslated.php` | La build y el listado de cadenas sin traducir. `lib.php` tiene las funciones que prueban los tests unitarios. |
| `generated/` | El resultado. No se versiona. |
| `tests/run.sh` | Los tests, los mismos que Actions: `po`, `unit`, `build` e `integration` (`tests/compose.yaml`, `tests/integration.py`). |
| `.github/workflows/ci.yml`, `release.yml` | Tests en cada push y pull request; release en cada tag. |

## Convenciones

- Mensajes de los scripts, comentarios del código y `README.md`, en inglés. `AGENTS.md` y `docs/`, en español.
- Solo Docker y Python 3 (biblioteca estándar) en el anfitrión. GNU gettext se usa desde un contenedor de Debian, y PHP desde la imagen de Xibo.
- **Acciones de GitHub:**
  - solo las oficiales (`actions/*`), fijadas por commit con la versión en un comentario;
  - para cambiar de versión: el commit del tag, que tenga al menos dos semanas, y la lista de sus entradas en `action.yml`;
  - los permisos, los mínimos: `contents: write` solo en el job que publica.
- **Releases:**
  - una release publicada no se sustituye nunca: una corrección es una revisión nueva;
  - el workflow publica los mismos ficheros que han pasado los tests (artefacto `generated`), no una build aparte.

## Forma de trabajo

- **Los commits y los tags los hace siempre el usuario** (van firmados). No hacer commit, tag ni push: dejar los cambios en el árbol de trabajo e indicar qué ficheros cambian.
- Un cambio cada vez: explicar, enseñar el diff, esperar el OK y aplicar.
- **Cambios de traducción:**
  1. proponer una tabla *texto en inglés → traducción de Xibo → propuesta*, con los criterios de `docs/traducir.md`, y esperar el OK;
  2. aplicar el cambio con `./build.sh --normalize` y `./build.sh`;
  3. enseñar el diff de `es.po`.
- Antes de dar por bueno un cambio: `tests/run.sh` y `shellcheck` en los scripts de bash. Con los workflows, también `actionlint`.
- Los tests no inician sesión en Xibo: comprueban solo lo que se ve sin sesión (la página de login y `es.json`).
- **Nunca** guardar cookies de sesión, tokens, contraseñas ni claves en ficheros del repo. La contraseña de `tests/compose.yaml` es un valor de prueba de un MySQL desechable. Si el usuario pasa información con secretos sin censurar, avisarle para que los rote.
