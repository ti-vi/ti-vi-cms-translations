<?php
// Builds generated/: Xibo's own Spanish translation files with every string of es.po, the complete catalogue
// (every msgid of Xibo's locale/default.pot). They are mounted over the files of the Xibo image (README.md):
//   - es.mo    gettext catalogue of PHP and Twig (/var/www/cms/locale/es.mo)
//   - es.json  i18next catalogue of the React interface (/var/www/cms/web/app/locale/langs/es.json)
//   - VERSION  Xibo version the files were built for
// It also writes es.upstream.json (versioned, next to es.po): Xibo's own translation of every string of es.po
// (null if Xibo has none), to report what Xibo changed when the next version is built.
//
// Run it through build.sh: it runs this file in a throwaway container of the Xibo image of XIBO_VERSION, so it
// reads the original files of the image with Xibo's own gettext library. Procedure: docs/traducir.md.
//
// Usage (inside the container): php build.php <xibo-version>

declare(strict_types=1);

require '/var/www/cms/vendor/autoload.php';
require __DIR__ . '/lib.php';

use Gettext\Generator\MoGenerator;
use Gettext\Loader\MoLoader;
use Gettext\Loader\StrictPoLoader;
use Gettext\Translation;

const IMAGE_MO = '/var/www/cms/locale/es.mo';
const IMAGE_JSON = '/var/www/cms/web/app/locale/langs/es.json';
const IMAGE_POT = '/var/www/cms/locale/default.pot';
const CATALOGUE = __DIR__ . '/es.po';
const GENERATED = __DIR__ . '/generated';
const UPSTREAM = __DIR__ . '/es.upstream.json';

// ── Arguments ─────────────────────────────────────────────────────────────────
$version = $argv[1] ?? '';
if ($version === '' || isset($argv[2])) {
    fail('Usage: php build.php <xibo-version>   (run build.sh instead)');
}

// ── Xibo's own files ──────────────────────────────────────────────────────────
// If any of them is missing, this Xibo version translates in another way: review lib/Helper/Translate.php
// and web/app/assets/i18n-*.js before changing the mounts (README.md).
foreach ([IMAGE_MO, IMAGE_JSON, IMAGE_POT] as $file) {
    if (!is_file($file)) {
        fail("$file is not a file in the Xibo $version image: Xibo changed how it loads translations (docs/traducir.md).");
    }
}
$mo = (new MoLoader())->loadFile(IMAGE_MO);
$json = readJsonMap(IMAGE_JSON, "Xibo's es.json");
// Strict loader: the plain one skips the msgid "0" (empty() in PHP)
$pot = (new StrictPoLoader())->loadFile(IMAGE_POT);

// Self-test: es.json must be es.mo converted with toI18next(), string by string. If not, Xibo changed how it
// builds one from the other, and a change written once could no longer generate both files.
$moMap = [];
foreach ($mo as $entry) {
    if ($entry->getContext() !== null || $entry->getPlural() !== null) {
        fail('es.mo has entries with context or plural forms, which this script does not handle: ' . quote($entry->getOriginal()));
    }
    $moMap[$entry->getOriginal()] = $entry->getTranslation();
}
$mismatches = [];
foreach ($moMap as $original => $translation) {
    if (!array_key_exists($original, $json)) {
        $mismatches[] = 'only in es.mo: ' . quote($original);
    } elseif (toI18next($translation) !== $json[$original]) {
        $mismatches[] = 'different: ' . quote($original) . ' → es.mo ' . quote($translation) . ', es.json ' . quote($json[$original]);
    }
}
foreach (array_diff_key($json, $moMap) as $original => $_) {
    $mismatches[] = 'only in es.json: ' . quote((string) $original);
}
if ($mismatches) {
    fail("Xibo's es.json is no longer es.mo with the placeholder conversion of this script (" . count($mismatches)
        . " differences, first ones below). Review how this Xibo version builds them (docs/traducir.md).\n  "
        . implode("\n  ", array_slice($mismatches, 0, 10)));
}
echo "Xibo $version: " . count($moMap) . " Spanish strings in es.mo and es.json, " . count($pot) . " strings in default.pot.\n";

// ── The catalogue ─────────────────────────────────────────────────────────────
// es.po: a standard gettext file (msgid = Xibo's English string). Read strictly: a syntax error stops
// the build instead of dropping entries. Only plain, reviewed entries: no context, plural forms, fuzzy or obsolete.
if (!is_file(CATALOGUE)) {
    fail('es.po not found.');
}
try {
    $po = (new StrictPoLoader())->loadFile(CATALOGUE);
} catch (Throwable $e) {
    fail('es.po is not a valid PO file: ' . $e->getMessage());
}
if ($po->getHeaders()->getLanguage() !== 'es') {
    fail('es.po must have the header "Language: es".');
}
$catalogue = [];
foreach ($po as $entry) {
    $what = quote($entry->getOriginal());
    if ($entry->isDisabled()) {
        fail("es.po has an obsolete entry (#~): $what. Remove it.");
    }
    if ($entry->getContext() !== null || $entry->getPlural() !== null) {
        fail("es.po: $what has msgctxt or msgid_plural, which Xibo's catalogues do not use.");
    }
    if ($entry->getFlags()->has('fuzzy')) {
        fail("es.po: $what is marked fuzzy. Review it and remove the flag.");
    }
    $catalogue[$entry->getOriginal()] = $entry->getTranslation();
}
$previous = is_file(UPSTREAM) ? readJsonMap(UPSTREAM, 'es.upstream.json') : [];

$errors = [];
$counts = ['new' => 0, 'attention' => 0, 'unchanged' => 0, 'same' => 0];
$upstream = [];
ksort($catalogue, SORT_STRING);
foreach ($catalogue as $original => $text) {
    $original = (string) $original;
    $xibo = $moMap[$original] ?? null;
    $upstream[$original] = $xibo;
    $lines = ['Xibo:  ' . quote($xibo), 'es.po: ' . quote($text)];

    if ($pot->find(null, $original) === null) {
        $status = 'ERROR: not a string of Xibo ' . $version . ' (not in default.pot; Xibo may have changed its English text)';
        $errors[] = $original;
    } elseif ($text === null || trim($text) === '') {
        $status = 'ERROR: empty translation';
        $errors[] = $original;
    } elseif ($original === '0' && $text !== '0') {
        $status = 'ERROR: "0" can only stay "0" (Xibo\'s gettext library cannot write it to es.mo)';
        $errors[] = $original;
    } elseif (placeholders($text) !== placeholders($original)) {
        $status = 'ERROR: placeholders differ from the English text (same %s/%d in the same order, same %name%, {{name}}, :name, ${name} and {0})';
        $errors[] = $original;
    } elseif (!array_key_exists($original, $previous)) {
        if ($text === $xibo) {
            $counts['same']++;
            continue;
        }
        $status = 'new';
    } elseif ($previous[$original] === $xibo) {
        $counts['unchanged']++;
        continue;
    } elseif ($previous[$original] === null) {
        $status = "Xibo translates it now: keep ours or take Xibo's";
    } else {
        $status = 'Xibo changed its translation (it was ' . quote($previous[$original]) . "): keep ours or take Xibo's";
    }
    if (!str_starts_with($status, 'ERROR')) {
        $counts[$status === 'new' ? 'new' : 'attention']++;
    }
    echo "\n  [$status]\n    " . quote($original) . "\n    " . implode("\n    ", $lines) . "\n";
}
foreach (array_diff_key($previous, $catalogue) as $original => $_) {
    echo "\n  [removed from es.po] " . quote((string) $original) . "\n";
}
// Strings of this Xibo version that es.po lacks (after an upgrade, its new strings): they keep Xibo's translation,
// or show in English. Translate them and add them to es.po.
$untranslated = [];
foreach ($pot as $entry) {
    if ($entry->getOriginal() !== '' && !array_key_exists($entry->getOriginal(), $catalogue)) {
        $untranslated[] = $entry->getOriginal();
    }
}
foreach ($untranslated as $original) {
    echo "\n  [not in es.po] " . quote($original) . "\n    Xibo:  " . quote($moMap[$original] ?? null) . "\n";
}
echo "\n" . count($catalogue) . " strings in es.po: {$counts['new']} new, {$counts['attention']} to review, "
    . count($errors) . " with errors, {$counts['unchanged']} with no change in Xibo, {$counts['same']} same as Xibo.\n"
    . count($untranslated) . ' strings of default.pot are not in es.po.' . "\n";
if ($errors) {
    fail(count($errors) . ' strings have errors (see above). Nothing was written.');
}

// ── Build ─────────────────────────────────────────────────────────────────────
// Xibo extracts some React strings with their quotes escaped (\"), but the interface asks for them with plain
// quotes: es.json also gets them under that key, or they would show in English.
// Xibo's gettext library skips the msgid "0" (empty() in PHP); gettext and i18next show it as it is anyway, so
// es.po has it only to match default.pot.
$writable = array_diff_key($catalogue, ['0' => true]);
$reactKeys = [];
foreach ($writable as $original => $text) {
    $original = (string) $original;
    $entry = $mo->find(null, $original);
    if ($entry === null) {
        $mo->add(Translation::create(null, $original)->translate($text));
    } else {
        $entry->translate($text);
    }
    $json[$original] = toI18next($text);
    $reactKey = str_replace('\\"', '"', $original);
    if ($reactKey !== $original && $pot->find(null, $reactKey) === null) {
        $reactKeys[$reactKey] = toI18next(str_replace('\\"', '"', $text));
    }
}
$json = array_replace($json, $reactKeys);
$expected = array_replace($moMap, array_map('strval', $writable));

$files = [
    GENERATED . '/es.mo' => (new MoGenerator())->includeHeaders()->generateString($mo),
    GENERATED . '/es.json' => encodeJson($json),
    GENERATED . '/VERSION' => "$version\n",
    UPSTREAM => encodeJson($upstream) . "\n",
];

// Read the result back as Xibo and i18next will: every string must be Xibo's or ours, nothing else.
$built = [];
foreach ((new MoLoader())->loadString($files[GENERATED . '/es.mo']) as $entry) {
    $built[$entry->getOriginal()] = $entry->getTranslation();
}
ksort($built, SORT_STRING);
ksort($expected, SORT_STRING);
if ($built !== $expected) {
    fail('The generated es.mo does not read back as expected. Nothing was written.');
}
if (json_decode($files[GENERATED . '/es.json'], true) != array_replace(array_map('toI18next', $expected), $reactKeys)) {
    fail('The generated es.json does not read back as expected. Nothing was written.');
}

// ── Write ─────────────────────────────────────────────────────────────────────
if (!is_dir(GENERATED) && !mkdir(GENERATED)) {
    fail('Cannot create generated/.');
}
foreach ($files as $path => $content) {
    if (file_put_contents($path, $content) === false) {
        fail('Cannot write ' . substr($path, strlen(__DIR__) + 1) . '.');
    }
}
echo "generated/ and es.upstream.json written (Xibo $version).\n";
