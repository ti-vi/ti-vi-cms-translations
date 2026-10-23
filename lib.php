<?php
// Functions shared by build.php and the tests (tests/unit.php). Plain PHP: they do not need Xibo's libraries.

declare(strict_types=1);

function fail(string $message): never
{
    // To stdout, like the report: through docker run, stderr could overtake it
    echo "\nError: $message\n";
    exit(1);
}

function quote(?string $s): string
{
    return $s === null ? '(untranslated)' : json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Placeholders of a string, in the order the conversion to i18next reads them: the sprintf ones (%s, %d,
// %1$s) in order, then the named ones (%name%), the i18next ones of the React strings ({{count}}) and the ones
// of the JavaScript strings of the Twig views (:from, ${name}, {0}) as sorted lists. A translation must keep
// exactly the same.
function placeholders(string $s): array
{
    $sprintf = '/%(?:\d+\$)?[sd]/';
    preg_match_all($sprintf, $s, $m);
    $positional = $m[0];
    preg_match_all('/%[A-Za-z_][A-Za-z0-9_]*%/', preg_replace($sprintf, '', $s), $m);
    $named = $m[0];
    sort($named);
    preg_match_all('/\{\{[^{}]+\}\}/', $s, $m);
    $i18next = $m[0];
    sort($i18next);
    preg_match_all('/(?<![\w:\/]):[a-z][A-Za-z]*|\$\{\w+\}|(?<!\{)\{\d+\}(?!\})/', $s, $m);
    $js = $m[0];
    sort($js);
    return [$positional, $named, $i18next, $js];
}

// gettext text → i18next text, as Xibo builds es.json from es.mo: %s → {{0}}, {{1}}… in order,
// %name% → {{name}}; %d stays. The self-test of build.php checks that this still gives Xibo's es.json.
function toI18next(string $s): string
{
    $i = 0;
    $s = preg_replace_callback('/%s/', function () use (&$i) {
        return '{{' . $i++ . '}}';
    }, $s);
    return preg_replace('/%([A-Za-z_][A-Za-z0-9_]*)%/', '{{$1}}', $s);
}

// Same format as Xibo's es.json (keys in byte order, 2-space indent, no final newline): re-encoding Xibo's
// own file gives the same bytes, so a diff with it shows only the changed strings.
function encodeJson(array $map): string
{
    ksort($map, SORT_STRING);
    $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    return str_replace("\n    ", "\n  ", $json);
}

// A JSON object of strings (or null): Xibo's es.json, or es.upstream.json
function readJsonMap(string $file, string $what): array
{
    $map = json_decode((string) file_get_contents($file), true);
    if (!is_array($map) || ($map !== [] && array_is_list($map))) {
        fail("$what ($file) is not a JSON object.");
    }
    foreach ($map as $key => $value) {
        if (!is_string($value) && $value !== null) {
            fail("$what ($file): the value of " . quote((string) $key) . ' is not a string.');
        }
    }
    return $map;
}
