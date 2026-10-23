# Traducir

Cómo está hecho el catálogo, qué criterios sigue y cómo se cambia. La instalación en un Xibo está en el [README](../README.md) (en inglés).

El catálogo corrige y completa la traducción al español de Xibo **sin modificar la imagen ni el contenedor**: se montan, de solo lectura, los ficheros de traducción de Xibo con todas las cadenas de `es.po` encima de los originales. Solo se traduce el español (`es`).

## Estado (Xibo 4.5.5)

`es.po` es el **catálogo completo** en español de Latinoamérica: las 6600 cadenas de `locale/default.pot`, todas revisadas a mano, en un fichero gettext estándar ([Launchpad](#publicar-el-catálogo-en-launchpad)). La interfaz entera sale en español, también Administración, Avanzado y Desarrollador, salvo [lo que no pasa por los catálogos](#lo-que-no-se-puede-traducir-así).

- **2253 que Xibo no traducía**:
  - 1568 de las pantallas de uso diario (login, menú de usuario, panel, Programación, Diseño, Biblioteca, Pantallas e Informes);
  - 684 de Administración, Avanzado, Desarrollador, mensajes del servidor y vistas Twig;
  - la cadena `0`.

  Seis son nuevas de 4.5.5.
- **2396 correcciones** a traducciones de Xibo: se revisaron a mano las 4347 que trae Xibo y se corrigió más de la mitad. Los errores eran de varios tipos:
  - **terminología**: *Layout* como «diseño», *Display* como «players», «dispositivos» o «visualización», *Schedule* como «horario» o «calendario», *DataSet* como «fuente de datos», *Spots* como «Lugares» o «mancha», *Settings* como «Preferencias»;
  - **sentido**: «Currículum» por *Resume*, «Correrse» por *Run Intent*, «comandos de cáscara», una cadena que empezaba por «ChatGPT», textos en portugués y traducciones de otra cadena;
  - **marcadores rotos** (`_MAX_`);
  - **estilo**: tuteo mezclado con usted y mayúsculas a la inglesa.
- **1951 iguales a las de Xibo**, que se revisaron y estaban bien.

Las cadenas que faltaban se localizaron analizando el código de la interfaz ([buscar cadenas sin traducir](#buscar-cadenas-sin-traducir)). Todo se revisó con capturas de páginas y formularios en un CMS de prueba ([revisar en el navegador](#revisar-en-el-navegador)).

## Cómo traduce Xibo 4.5

Xibo tiene dos catálogos con las **mismas cadenas** (comprobado en 4.5.3):

| Catálogo | Fichero en la imagen | Qué traduce | Cuándo lo lee |
|---|---|---|---|
| gettext | `/var/www/cms/locale/es.mo` | PHP y Twig: página de login, editores de diapositivas y de listas de reproducción, formularios antiguos, mensajes de la API, módulos y sus propiedades, correos, informes | En cada petición (`lib/Helper/Translate.php`; Twig compila `{% trans %}` a llamadas `__()`, sin caché) |
| i18next | `/var/www/cms/web/app/locale/langs/es.json` | La interfaz React (`/app/`): menús, listados, formularios nuevos | Al cargar la interfaz (`web/app/assets/i18n-*.js`, `fetch` con `cache: 'no-cache'`) |

- **Claves:** la clave de cada cadena es su texto en inglés, el *msgid* de `locale/default.pot`. El JSON es plano, sin espacios de nombres.
- **Marcadores:** solo cambian entre los dos catálogos.
  - En el `.mo` son `%s`, `%d` y `%nombre%`.
  - En el JSON, `%s` pasa a `{{0}}`, `{{1}}`… (en orden), `%nombre%` a `{{nombre}}`, y `%d` se queda igual.
  - Las cadenas propias de React traen ya marcadores de i18next (`{{count}}`) y etiquetas (`<strong>`, `<browse>`), que se conservan tal cual.
- **Plurales de React:** son dos cadenas con sufijo, `… deleted successfully._one` y `…_other`. Se traducen las dos.
- **Sin traducción:** las cadenas salen en inglés.
- **Página de login:** no usa i18next. El servidor le pasa sus textos ya traducidos (`window.__LOGIN_CONFIG__.i18n`, desde `lib/Controller/Login.php` con `__()`). Se cambian en el `.mo` y se pueden comprobar sin sesión.
- **Idioma:**
  - Se usa el de la preferencia del usuario o, si no tiene, el del navegador, porque Xibo trae activada la detección (`DETECT_LANGUAGE=1`).
  - Si el navegador no pide un idioma que Xibo tenga, se usa el idioma por defecto (`DEFAULT_LANGUAGE`).
  - Un navegador configurado en inglés ve la interfaz en inglés.
  - Desactivar la detección en *Administración → Ajustes* hace que todos vean el idioma por defecto.
- Xibo no ofrece ninguna forma de sobrescribir traducciones (ni en `custom/` ni en `brand/config.json`), por eso se montan los ficheros.

### Lo que no se puede traducir así

Comprobado en 4.5.3. Son textos que no pasan por los catálogos:

| Texto | Origen |
|---|---|
| Títulos de los informes (*API Request History*, *Proof of Play*, *Library Usage*…) en la lista de informes y en la cabecera de cada uno | Campo `description` de `reports/*.report` de la imagen, que la interfaz muestra sin traducir |
| *AND* / *OR* del filtro de etiquetas | Valores fijos del código |
| Leyenda de «Uso de la biblioteca» (*Image*, *Generic File*, *Player Software*…) | La API envía el nombre interno del tipo |
| Fechas largas (*Tuesday, Oct 13, 2026*) | Librería de fechas de la interfaz |
| *Root Folder*, la franja horaria *Custom* (*User specifies the from/to date*), la descripción de la diapositiva por defecto | Datos de la base de datos |
| Nombres y descripciones de grupos de usuarios (*Users*, *Content Manager*…), tareas (*Daily Maintenance*…), transiciones (*Fade In*…) y tipos de objeto del registro de auditoría (*Playlist*, *Font*) | Datos de la base de datos, que la interfaz muestra sin pasarlos por i18next (aunque algunos están en `default.pot`) |
| Nombres y descripciones de los conectores (*Xibo Dashboard Service*, *Get Currencies and Stocks data*…) y de los módulos (*Instruct a Display to execute a command…*) en Administración | Los envía la API tal como están en el código o en las definiciones de los módulos |
| Título de la ventana del navegador (*Displays \| <nombre del CMS>*) | La interfaz lo forma con el nombre interno de la ruta (`labelKey`), sin traducir |
| «HACE NAN MESES» en las noticias del panel | Fallo de Xibo con las noticias desactivadas, no de traducción |

## Ficheros

| Fichero | Qué es |
|---|---|
| `es.po` | **El catálogo completo**, la única fuente que se edita. Fichero gettext estándar: se abre con Poedit y lo valida GNU gettext (`tests/run.sh po`). |
| `XIBO_VERSION` | La versión de Xibo para la que se construye: `default.pot`, `es.mo` y `es.json` salen de su imagen. |
| `es.upstream.json` | La traducción de Xibo de cada cadena de `es.po` (`null` si no la tenía) en la última build. Sirve para avisar de lo que cambie Xibo en la siguiente versión. Se versiona: la build lo escribe, y se hace commit junto con `es.po`. |
| `build.sh`, `build.php`, `lib.php` | Construyen `generated/` y `es.upstream.json`. Solo necesitan Docker. |
| `untranslated.php` | Lista las cadenas de la interfaz que siguen sin traducir (`./build.sh --untranslated`). |
| `generated/` | El resultado (`es.mo`, `es.json`, `VERSION`): los ficheros de la imagen de `XIBO_VERSION` con todas las cadenas de `es.po`, en el formato de cada catálogo. No se versiona: lo publica cada release. |
| `tests/` | Los tests (`tests/run.sh`): los mismos que ejecuta GitHub Actions. |

### `es.po`

```po
msgid "Interrupt Layout"
msgstr "Diapositiva de interrupción"

msgid "{{count}} display(s) deleted successfully._one"
msgstr "{{count}} pantalla eliminada correctamente."
```

- **Una entrada por cada `msgid` de `default.pot`**, también las que no cambian la traducción de Xibo: el fichero es la traducción entera, no una lista de cambios.
- **`msgid`:** el texto en inglés de Xibo, exacto: mayúsculas, puntos, espacios, y las `\"` que trae `default.pot` en algunas cadenas de React.
- **`msgstr`:** el español, con los marcadores del inglés:
  - los de gettext: `%s`, `%d`, `%nombre%`;
  - los `{{…}}` y las etiquetas de React;
  - los de las vistas Twig: `:from`, `${recurrenceDetail}`, `{0}`.

  Nunca `{{0}}`: la conversión la hace `build.php`. Los valores de código (`string`, `json`, `on`, `eq`…), las unidades y los nombres propios se traducen por sí mismos.
- **Formato de GNU gettext:**
  - cabecera estándar: `Project-Id-Version: xibo`, `POT-Creation-Date` del `default.pot`, `PO-Revision-Date` con la fecha del último cambio, `Language: es` y `Plural-Forms`;
  - entradas ordenadas por `msgid`, y líneas largas partidas como lo hace `msgcat`;
  - sin `msgctxt` ni `msgid_plural`: Xibo no los usa, y sus plurales de React son dos `msgid` con `_one` y `_other`;
  - sin marcas `fuzzy` ni entradas obsoletas (`#~`).

  `./build.sh --normalize` deja el formato canónico tras editarlo; si ya lo tiene, no cambia nada.
- **Comentarios:** se puede añadir un comentario de traductor (`# …`) sobre una entrada para explicar una decisión.
- **Una cadena se deja en inglés** (`msgstr` igual al `msgid`, con un comentario) si Xibo compara su texto traducido.
  - En 4.5.5 solo pasa con *Too many attempts. Please wait and try again.*: `lib/Controller/Login.php` busca `Too many attempts` en ese mensaje para saber que el login está bloqueado.
  - Si se traduce, un login bloqueado responde 401 en lugar de 429, y cada intento nuevo vuelve a empezar el bloqueo de 15 minutos.
  - El usuario solo lo ve, en inglés, si se bloquean sus códigos de 2FA.
  - La cadena parecida *… wait before trying again.* sí se traduce.

### Qué hace `build.sh`

`build.sh` ejecuta `build.php` en un contenedor desechable de la imagen de Xibo de `XIBO_VERSION` (`docker run --rm --network none`), así que lee los ficheros originales de la imagen con la misma librería de gettext que usa Xibo:

1. Comprueba que existen `locale/es.mo`, `web/app/locale/langs/es.json` y `locale/default.pot`.
2. Comprueba que el `es.json` de Xibo es exactamente su `es.mo` con la conversión de marcadores de arriba. Si no, una cadena escrita una vez ya no daría los dos ficheros.
3. Lee `es.po` de forma estricta (un error de sintaxis para la build). Rechaza `fuzzy`, entradas obsoletas, `msgctxt`, `msgid_plural` y una cabecera sin `Language: es`.
4. Valida cada cadena:
   - el texto en inglés existe en `default.pot`;
   - el español no está vacío;
   - conserva los marcadores: los `%s`/`%d` en el mismo orden, y los mismos `%nombre%`, `{{…}}`, `:nombre`, `${…}` y `{0}`.
5. Informa de cada cadena que necesita atención ([nueva versión de Xibo](#nueva-versión-de-xibo)) y lista las de `default.pot` que faltan en `es.po`.
6. Escribe todas las cadenas en los dos catálogos, con dos ajustes por fallos de Xibo:
   - **cadenas de React con `\"`:** `default.pot` las trae así, pero la interfaz las pide con comillas normales, así que van también al `es.json` con comillas normales. Si no, salen en inglés: los botones de *Include "Add Campaign" button* en las funciones de los grupos, *Renamed to "{{name}}"*, *Folder "{{name}}" created*…
   - **la cadena `0`:** la librería de gettext de Xibo la descarta (para PHP, `"0"` está vacía); solo está en `es.po` para que coincida con `default.pot`.
7. Comprueba que el resultado se lee bien: todas las cadenas son las de Xibo o las de `es.po`. El `es.json` generado tiene el mismo formato que el de Xibo, así que `diff` entre los dos solo enseña las cadenas cambiadas o añadidas.

```bash
./build.sh                 # genera generated/ y es.upstream.json, e informa de lo que hay que revisar
./build.sh --untranslated  # cadenas de la interfaz que siguen sin traducir, por fichero
./build.sh --normalize     # es.po en el formato canónico de GNU gettext
```

## Tests

`tests/run.sh` ejecuta lo mismo que GitHub Actions en cada push y pull request ([`ci.yml`](../.github/workflows/ci.yml)). Solo necesita Docker:

| Test | Qué comprueba |
|---|---|
| `po` | Con GNU gettext (Debian 13), contra el `default.pot` de la imagen: `es.po` está en formato canónico (`msgcat --sort-output` no cambia nada), es válido (`msgfmt --check`) y tiene todas las cadenas (`msgcmp`). |
| `unit` | `tests/unit.php`, con el PHP de la imagen: las reglas de los marcadores, la conversión a i18next y el formato del JSON (`lib.php`). |
| `build` | `./build.sh` termina sin errores y no cambia `es.upstream.json` (si lo cambia, falta hacer commit de él). |

```bash
tests/run.sh                  # todos, en este orden
tests/run.sh po unit          # solo esos
```

Los tests no levantan ningún Xibo: comprueban el catálogo y la build, que ya verifica las rutas de los dos ficheros en la imagen y lee el resultado con la librería de Xibo. Que un CMS use de verdad los ficheros montados solo puede cambiar con una versión nueva de Xibo, y se comprueba a mano en un CMS de prueba ([nueva versión de Xibo](#nueva-versión-de-xibo)).

Al terminar en local, `docker rmi debian:13-slim` deja Docker limpio.

## Criterios de traducción

- **Español de Latinoamérica**: «video», «Ingrese», «Presione», tecla «Enter», «mouse», «costo», «capacitación».
- Trato de **usted** («Seleccione», «¿Seguro que quiere…?»), nunca tuteo.
- **Glosario** (aplicado a todo el catálogo, también a las cadenas de Xibo):

  | Inglés | Español | | Inglés | Español |
  |---|---|---|---|---|
  | Layout | diapositiva | | Schedule | programación (verbo: programar) |
  | Overlay / Interrupt Layout | diapositiva superpuesta / de interrupción | | Daypart | franja horaria |
  | Display | pantalla | | Report / Report Schedule | informe / programación de informe (nunca «reporte») |
  | Display Group | grupo de pantallas | | Proof of Play | prueba de reproducción |
  | Display Profile / Settings | perfil de pantalla / Configurar pantallas | | Share of Voice | cuota de emisión |
  | Sync Group | grupo de sincronización | | Spot (sublista de reproducción) | espacio |
  | Campaign / Ad Campaign | campaña / campaña publicitaria | | Checkout (diapositiva) | editar (checkout); *checked out*: en edición |
  | Playlist / Sub-Playlist | lista de reproducción / sublista de reproducción | | Add / Delete / Remove / Clear | agregar / eliminar / quitar / limpiar |
  | Media | medios (un medio) | | Settings / Preferences | configuración / preferencias |
  | DataSet | conjunto de datos | | Collect (recopilación de datos del Player) | recopilación (nunca «recogida», que en Latinoamérica tiene otras connotaciones) |
  | Data Connector | conector de datos (función distinta del conjunto de datos: datos en tiempo real) | | Player | Player (siempre con mayúscula; nunca «reproductor») |
  | Menu Board | tablero de menú | | Widget / Region / Zone | widget / región / zona |

- **Mayúsculas:** solo al principio, como manda la norma del español («Agregar grupo de pantallas»). Las excepciones son los nombres propios, el término Player y los nombres de botones, pestañas u opciones citados en una frase («haga clic en Aplicar», «la pestaña Configurar»).
- **Puntuación:** comillas españolas «…»; los puntos suspensivos y la puntuación final, como en el inglés («…» o «...»).
- **Se dejan en inglés** las unidades y términos técnicos: KB/MB/GB, KiB…, HH/MM, HTTP y sus métodos, RS232, OSD, USB, `text/plain`, nombres de sistemas operativos, fabricantes y servicios, y los valores que la interfaz usa como código (`string`, `json`, `on`, `eq`…). En `es.po` se traducen por sí mismos.
- **Formatos de fecha:** su referencia en la configuración describe lo que escribe PHP, que es en inglés: «Jan a Dec», «st, nd, rd o th».
- **Textos repetidos:** un mismo texto en inglés se comparte en todas las pantallas donde aparece, así que una traducción tiene que valer para todas.

## Cambiar una cadena

Sirve igual para una traducción mal hecha que para un texto que sale en inglés.

1. **Localizar la cadena** en `es.po`, por el texto en español o en inglés: están todas. Las largas están partidas en varias líneas, así que conviene buscar un trozo corto. Para saber dónde se usa, buscarla en el `default.pot` de la imagen:
   ```bash
   grep -n -i -F -B1 'texto en español' es.po
   IMG=ghcr.io/xibosignage/xibo-cms:release-$(<XIBO_VERSION)
   docker run --rm --network none --entrypoint grep "$IMG" -i -F -B3 'msgid "Texto en inglés' /var/www/cms/locale/default.pot
   ```
   Si hay varias cadenas parecidas (mayúsculas, puntos finales, plurales `_one`/`_other`), se cambian todas las que correspondan. Si el texto no aparece en ningún catálogo, ver [lo que no se puede traducir](#lo-que-no-se-puede-traducir-así).
2. **Cambiar** las entradas en `es.po` con los [criterios](#criterios-de-traducción), y actualizar `PO-Revision-Date`.
3. **Normalizar, construir y probar**: `./build.sh --normalize`, `./build.sh` (revisar su informe) y `tests/run.sh`.
4. **Comprobar en un CMS de prueba**: montar `generated/` como dice el README y buscar la cadena en PHP y en el JSON servido:
   ```bash
   docker compose exec -T cms-web php -r 'require "/var/www/cms/vendor/autoload.php";
       $t = (new Gettext\Loader\MoLoader)->loadFile("/var/www/cms/locale/es.mo");
       echo $t->find(null, "Texto en inglés")?->getTranslation(), "\n";'
   curl -s http://127.0.0.1:<puerto>/app/locale/langs/es.json | grep -F 'Texto en inglés'
   ```
   Y en el navegador ([revisar en el navegador](#revisar-en-el-navegador)).
5. **Commit** de `es.po` y `es.upstream.json`. `generated/` no se versiona.

## Buscar cadenas sin traducir

Con el catálogo completo, `./build.sh` ya lista las cadenas de `default.pot` que faltan en `es.po` (*not in es.po*): tras pasar a otra versión de Xibo, son sus cadenas nuevas. Para saber dónde sale cada una:

- `./build.sh --untranslated` busca en el código de la interfaz React de la imagen (`web/app/assets/*.js`) cada cadena de `default.pot` que siga sin traducción en el `es.json` generado. Las lista agrupadas por fichero de la interfaz.
  - Los ficheros se llaman como la pantalla o el componente (`Displays`, `Layouts`, `ScheduleEventModal`, `UserGroups`…), con un sufijo de hash que el listado quita.
  - Las rutas de cada menú están en `web/app/assets/appRoutes-*.js` (`path:` y el `import()` de cada página).
  - En 4.5.5 no lista ninguna. GitHub Actions lo deja en el resumen de cada ejecución.
- Las que no salen ahí son de PHP o de las vistas Twig: las referencias `#:` de `default.pot` dicen el fichero (`lib/Controller/…`, o `cache/…` para Twig compilado).

Detecta los textos que aparecen literalmente en el código. Los que se construyen al vuelo no los ve, y por eso se revisa también en el navegador.

## Revisar en el navegador

En un CMS de prueba, con un Chromium sin interfaz manejado por el protocolo DevTools (CDP) desde Node. No hacen falta dependencias: Node 22 o posterior trae `WebSocket`.

- Lanzar `chromium --headless=new --remote-debugging-port=… --ignore-certificate-errors --host-resolver-rules="MAP cms.example.com 127.0.0.1"`.
- Antes de nada, `Network.setExtraHTTPHeaders` con `Accept-Language: es-ES,es;q=0.9`: por la detección de idioma, sin esa cabecera la interfaz sale en inglés.
- Iniciar sesión en `/login` con el usuario por defecto del CMS de prueba. Las contraseñas, solo en variables de entorno; nunca en el repo.
- Para cada ruta del menú (`/dashboard`, `/schedule/events`, `/design/layout`, `/library/media`, `/displays/all-displays`, `/reporting/all-reports`, `/administration/settings`, `/advanced/log`, `/developer/template`…), guardar la captura (`Page.captureScreenshot`) y el texto visible (`document.body.innerText`).
- Comparar el texto con `default.pot` y con el `es.json` generado:
  - una línea que sea un `msgid` sin traducción es una cadena pendiente;
  - una línea en inglés que no está en `default.pot` es de [lo que no se puede traducir](#lo-que-no-se-puede-traducir-así).
- Abrir también algunos formularios (los botones «Agregar…» de cada página, los «Configurar» de los conectores), las secciones de la configuración y el menú de usuario, porque sus textos no se ven en la página.

Las páginas de un CMS de prueba están vacías: las filas de las tablas y muchos mensajes (errores, confirmaciones) no se ven. Esas cadenas las cubre la búsqueda en el código.

## Nueva versión de Xibo

Una versión nueva trae sus propios `es.mo`, `es.json` y `default.pot`: el catálogo se vuelve a aplicar sobre los suyos y hay que ponerlo al día con las cadenas que cambien.

1. Poner la versión nueva en `XIBO_VERSION` y ejecutar `./build.sh` (descarga la imagen si no está).
2. Si se para porque falta un fichero o porque falla la conversión de marcadores, Xibo ha cambiado su forma de traducir. Hay que revisar `lib/Helper/Translate.php` y `web/app/assets/i18n-*.js` de la versión nueva, y adaptar `build.php` y los montajes del README.
3. Revisar el informe, que compara con `es.upstream.json` (la traducción de Xibo en la build anterior):
   - **ERROR: not a string of Xibo …**: Xibo cambió o quitó ese texto en inglés. Si lo cambió, la cadena nueva sale en *not in es.po*: pasar la traducción a la cadena nueva, adaptada. Si lo quitó, borrar la entrada.
   - **not in es.po**: cadenas nuevas de la versión. Traducirlas con los [criterios](#criterios-de-traducción) y localizar dónde salen ([buscar cadenas sin traducir](#buscar-cadenas-sin-traducir)).
   - **Xibo translates it now**: antes no tenía traducción y ahora sí. Decidir si se queda la nuestra o la suya.
   - **Xibo changed its translation**: Xibo corrigió su traducción. Decidir igual.

   Repasar también:
   - [lo que no se puede traducir](#lo-que-no-se-puede-traducir-así), por si ha cambiado;
   - las [cadenas que se dejan en inglés](#espo): que Xibo sigue comparando ese texto y que no compara otros (`grep -rn 'str_contains(\$e->getMessage()' lib/` en la imagen nueva).
4. Editar `es.po`, actualizar su `POT-Creation-Date` con la del `default.pot` nuevo, normalizarlo, construir y ejecutar `tests/run.sh`.
5. Comprobar en un CMS de prueba de la versión nueva, con `generated/` montado como dice el README: la página de login en español (pedida con `Accept-Language: es`), `/app/locale/langs/es.json` igual al generado, y un repaso en el navegador ([revisar en el navegador](#revisar-en-el-navegador)).
6. Commit de `XIBO_VERSION`, `es.po` y `es.upstream.json`, y publicar la release `<versión>-1`.

Las releases de la versión anterior siguen publicadas para quien no haya actualizado Xibo.

## Publicar una release

Una release es un tag `<versión de Xibo>-<revisión>` (por ejemplo `4.5.5-1`) sobre un commit de `main` cuyo `XIBO_VERSION` es esa versión. La revisión empieza en 1 para cada versión de Xibo y sube con cada corrección.

```bash
git tag -s 4.5.5-2 -m "4.5.5-2"      # firmado
git push origin 4.5.5-2
```

[`release.yml`](../.github/workflows/release.yml) hace lo siguiente:

1. Comprueba que el tag tiene ese formato y que corresponde a `XIBO_VERSION`.
2. Ejecuta los tests de `ci.yml` sobre ese tag.
3. Publica la release con los ficheros que pasaron los tests (`es.mo`, `es.json`, `VERSION`) y su `SHA256SUMS`.

**Una release publicada no se sustituye nunca:** quien la usa puede haber fijado su sha256. Una corrección es una revisión nueva. Si la ejecución falla antes de publicar (por ejemplo, un fallo pasajero de la red), se repite desde *Actions → Release → Run workflow* con el mismo tag.

## Publicar el catálogo en Launchpad

Las traducciones de Xibo se mantienen en [Launchpad](https://translations.launchpad.net/xibo) (proyecto `xibo`, plantilla `default.pot`), y de ahí salen los `es.mo` y `es.json` de cada versión. `es.po` está listo para subirlo: es la traducción completa de la plantilla de la versión de `XIBO_VERSION` (`msgcmp` sin errores), con cabecera estándar y en el formato de GNU gettext.

- En Launchpad: *Spanish (es)* → *Upload translation*, con una cuenta que tenga permiso de traducción en el proyecto. Si no lo tiene, la subida queda como sugerencia pendiente de revisión. Launchpad publica las traducciones con licencia BSD.
- Antes de subirlo, comprobar que `POT-Creation-Date` coincide con la plantilla que tiene Launchpad (la del `default.pot` de la última versión de Xibo). Si Launchpad ya tiene una plantilla más nueva, actualizar antes el catálogo a esa versión.
- Es la variante de Latinoamérica y lleva este glosario (*Layout* = «diapositiva»): cambia muchas traducciones que otros usuarios de Xibo en español ya conocen. Si se prefiere no imponerlo, se puede subir solo como sugerencias, o proponer únicamente las correcciones de errores (sentido, marcadores, portugués…).
- El catálogo es una reconstrucción propia, revisada cadena a cadena, así que la cabecera solo nombra a su autor, con su perfil de GitHub y sin correo: `Last-Translator`, `Language-Team` y la línea de autor del comentario inicial. Se mantienen así al editarlo (actualizando `PO-Revision-Date`). Launchpad pone además el traductor de la cuenta que sube el fichero.

Cuando una corrección llegue a una versión de Xibo, `build.sh` dejará de informar de esa cadena: la traducción de `es.po` y la de Xibo coinciden.

## Problemas

| Síntoma | Causa | Solución |
|---|---|---|
| El texto sigue igual en el CMS | `cms-web` no se ha recreado tras cambiar los ficheros, es otra cadena parecida, o es un texto que [no se puede traducir](#lo-que-no-se-puede-traducir-así) | `docker compose up -d --force-recreate cms-web`; comprobar el idioma del usuario y del navegador, y buscar la cadena |
| Toda la interfaz sale en inglés para un usuario | Su navegador pide inglés y la detección de idioma está activada | Preferencias del usuario → idioma español, o desactivar la detección en los ajustes del CMS |
| `cms-web` no arranca (error de montaje, *not a directory*) | Falta uno de los ficheros montados, y Docker creó un directorio en su lugar | Borrar ese directorio, descargar el fichero y `docker compose up -d cms-web` |
| *Cannot read the file '/var/www/cms/locale/es.mo', probably permissions* | El usuario del contenedor (`www-data`) no puede leer el fichero montado: permisos, SELinux (falta `,z`) o una carpeta en un sistema de ficheros que solo deja leer a su dueño (FUSE) | Permisos `644`, `:ro,z` con SELinux, o los ficheros en otra carpeta |
| `build.sh`: *not a valid PO file*, *fuzzy*, *obsolete* | Error de formato en `es.po` | Corregir la entrada que indica (la posición va en el mensaje) |
| `build.sh`: *placeholders differ* | La traducción no conserva los marcadores del inglés | Corregir la traducción en `es.po` |
| `build.sh`: *not in es.po* | Cadenas de `default.pot` que faltan en el catálogo (normalmente, nuevas de una versión de Xibo) | Traducirlas y añadirlas ([nueva versión de Xibo](#nueva-versión-de-xibo)) |
| `tests/run.sh po`: *not in the canonical format* | `es.po` editado sin normalizar | `./build.sh --normalize` |
| `tests/run.sh build`: *changed es.upstream.json* | Falta hacer commit del `es.upstream.json` que escribe la build | `./build.sh` y commit de `es.upstream.json` |
