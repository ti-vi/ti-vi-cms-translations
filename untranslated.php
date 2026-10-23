<?php
// Lists the strings of the React interface that are still untranslated in Spanish once generated/ is mounted,
// grouped by the interface file (chunk) that uses them, to find what to translate after a Xibo upgrade.
// Read-only.
//
// A string counts as used by a chunk when it appears in its code as a literal (`text` or "text") and is a msgid of
// default.pot; plural keys (text_one, text_other) count when their base text appears. Lowercase one-word literals
// (json, on, delete) only count when passed to a function, since most are code values, not texts. Chunks whose
// name says nothing (a hash) are shown under that name: look them up in web/app/assets/ of the image.
//
// Run it through build.sh --untranslated (throwaway container of the Xibo image of XIBO_VERSION).
// Usage (inside the container): php untranslated.php <xibo-version>

declare(strict_types=1);

require '/var/www/cms/vendor/autoload.php';

use Gettext\Loader\PoLoader;

const ASSETS = '/var/www/cms/web/app/assets/';
const IMAGE_POT = '/var/www/cms/locale/default.pot';
const BUILT_JSON = __DIR__ . '/generated/es.json';

$version = $argv[1] ?? '';
foreach ([IMAGE_POT, BUILT_JSON] as $file) {
    if (!is_file($file)) {
        echo "Error: $file not found (run build.sh first).\n";
        exit(1);
    }
}
$built = json_decode((string) file_get_contents(BUILT_JSON), true);
if (trim((string) file_get_contents(__DIR__ . '/generated/VERSION')) !== $version) {
    echo "Error: generated/ was not built for Xibo $version. Run build.sh first.\n";
    exit(1);
}

// Every untranslated msgid, as the minifier writes its base text (with plain quotes: Xibo extracts some with \")
$wanted = [];
foreach ((new PoLoader())->loadFile(IMAGE_POT) as $entry) {
    $key = $entry->getOriginal();
    if ($key === '' || isset($built[$key])) {
        continue;
    }
    $base = str_replace('\\"', '"', preg_replace('/_(one|other)$/', '', $key));
    $tpl = '`' . str_replace(['\\', '`', '${'], ['\\\\', '\\`', '\\${'], $base) . '`';
    $dq = '"' . addcslashes($base, "\\\"\n") . '"';
    $wanted[$key] = [$tpl, $dq, preg_match('/^[a-z0-9_]+$/', $base) === 1];
}

$byChunk = [];
$total = [];
foreach (glob(ASSETS . '*.js') as $path) {
    $src = (string) file_get_contents($path);
    $found = [];
    foreach ($wanted as $key => [$tpl, $dq, $codeLike]) {
        if (!str_contains($src, $tpl) && !str_contains($src, $dq)) {
            continue;
        }
        if ($codeLike && !preg_match('/[\w$]\((?:' . preg_quote($tpl, '/') . '|' . preg_quote($dq, '/') . ')/', $src)) {
            continue;
        }
        $found[] = (string) $key;
        $total[$key] = true;
    }
    if ($found) {
        sort($found, SORT_STRING);
        $byChunk[preg_replace('/-[A-Za-z0-9_-]{8}\.js$/', '', basename($path))] = $found;
    }
}
uksort($byChunk, 'strcasecmp');
foreach ($byChunk as $chunk => $keys) {
    echo "\n== $chunk (" . count($keys) . ")\n";
    foreach ($keys as $key) {
        echo '  ' . json_encode($key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
echo "\nXibo $version: " . count($total) . ' untranslated strings used by ' . count($byChunk) . " interface files.\n";
