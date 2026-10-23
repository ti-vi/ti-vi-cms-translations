<?php
// Unit tests of lib.php: the placeholder rules that every translation must keep, the gettext → i18next
// conversion and the format of es.json. Plain PHP, no framework: run with the PHP of the Xibo image (tests/run.sh
// unit), or any PHP 8.1+:
//   php tests/unit.php

declare(strict_types=1);

require __DIR__ . '/../lib.php';

$failed = 0;
$passed = 0;

function same(string $what, mixed $expected, mixed $actual): void
{
    global $failed, $passed;
    if ($expected === $actual) {
        $passed++;
        return;
    }
    $failed++;
    echo "FAIL: $what\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
}

// ── placeholders() ────────────────────────────────────────────────────────────
same('sprintf placeholders keep their order',
    [['%s', '%d'], [], [], []], placeholders('%s has %d items'));
same('positional sprintf placeholders',
    [['%2$s', '%1$s'], [], [], []], placeholders('%2$s before %1$s'));
same('named placeholders are sorted',
    [[], ['%a%', '%b%'], [], []], placeholders('%b% and %a%'));
same('a named placeholder is not taken from two sprintf ones',
    [['%s', '%s'], [], [], []], placeholders('%s%s'));
same('i18next placeholders are sorted',
    [[], [], ['{{count}}', '{{name}}'], []], placeholders('{{name}} has {{count}}'));
same('JavaScript placeholders of the Twig views',
    [[], [], [], ['${name}', ':from', '{0}']], placeholders(':from to {0} by ${name}'));
same('a URL is not a :name placeholder',
    [[], [], [], []], placeholders('See https://example.com/a:b'));
same('{{0}} is i18next, not {0}',
    [[], [], ['{{0}}'], []], placeholders('{{0}}'));
same('the translation must keep the same placeholders',
    placeholders('Deleted %s of %d'), placeholders('Se eliminaron %s de %d'));
same('a translation that swaps sprintf placeholders differs',
    false, placeholders('%s of %d') === placeholders('%d de %s'));
same('a translation that drops one differs',
    false, placeholders('%name% deleted') === placeholders('eliminado'));

// ── toI18next() ───────────────────────────────────────────────────────────────
same('%s becomes {{0}}, {{1}}… in order', '{{0}} of {{1}}', toI18next('%s of %s'));
same('%name% becomes {{name}}', 'Hello {{name}}', toI18next('Hello %name%'));
same('%d stays', '%d items', toI18next('%d items'));
same('i18next placeholders and tags stay', '<strong>{{count}}</strong>', toI18next('<strong>{{count}}</strong>'));
same('mixed', '{{0}} by {{user}}: %d', toI18next('%s by %user%: %d'));

// ── encodeJson() ──────────────────────────────────────────────────────────────
same('keys in byte order, 2-space indent, no final newline',
    "{\n  \"B\": \"b\",\n  \"a\": \"á/\"\n}", encodeJson(['a' => 'á/', 'B' => 'b']));
same('an empty map is an object', '{}', encodeJson([]));
same('numeric keys stay keys', "{\n  \"0\": \"0\"\n}", encodeJson(['0' => '0']));
same('quotes and backslashes are escaped', "{\n  \"\\\"x\\\\\": \"y\"\n}", encodeJson(['"x\\' => 'y']));

// ── readJsonMap() ─────────────────────────────────────────────────────────────
$tmp = tempnam(sys_get_temp_dir(), 'unit');
file_put_contents($tmp, '{"a": "b", "c": null}');
same('a map of strings and null', ['a' => 'b', 'c' => null], readJsonMap($tmp, 'test'));
unlink($tmp);

echo "\n$passed passed, $failed failed.\n";
exit($failed ? 1 : 0);
