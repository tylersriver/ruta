#!/usr/bin/env php
<?php

/**
 * Re-runnable router benchmark.
 *
 * Compares ruta (in-memory and file-cached) against nikic/fast-route
 * (simple and cached dispatcher) and symfony/routing (UrlMatcher and
 * compiled matcher) across route-set shapes, sizes and dispatch scenarios.
 *
 * Usage:
 *   composer install
 *   php run.php [--routes=60,600,1200] [--shapes=rest,static]
 *               [--subjects=ruta,fastroute,...] [--iterations=20000]
 *               [--repeats=7] [--warmup=2000]
 */

use Ruta\Benchmark\RouteSetGenerator;
use Ruta\Benchmark\Runner;
use Ruta\Benchmark\Subject\FastRouteCachedSubject;
use Ruta\Benchmark\Subject\FastRouteSubject;
use Ruta\Benchmark\Subject\RutaCachedSubject;
use Ruta\Benchmark\Subject\RutaSubject;
use Ruta\Benchmark\Subject\SymfonyCompiledSubject;
use Ruta\Benchmark\Subject\SymfonySubject;

require __DIR__ . '/vendor/autoload.php';

$options = getopt('', ['routes::', 'shapes::', 'subjects::', 'iterations::', 'repeats::', 'warmup::', 'help']);
if (isset($options['help'])) {
    echo <<<TXT
    Usage: php run.php [options]

      --routes=60,600,1200   comma separated route-set sizes
      --shapes=rest,static   route-set shapes to benchmark
      --subjects=...         ruta, ruta-cached, fastroute, fastroute-cached,
                             symfony, symfony-compiled (default: all)
      --iterations=20000     dispatches per measurement
      --repeats=7            measurements per cell (median reported)
      --warmup=2000          warmup dispatches before measuring

    TXT;
    exit(0);
}

$csv = fn(string $value): array => array_filter(array_map('trim', explode(',', $value)));

$routeCounts = array_map('intval', $csv($options['routes'] ?? '60,600,1200'));
$shapes = $csv($options['shapes'] ?? 'rest,static');
$iterations = (int)($options['iterations'] ?? 20_000);
$repeats = (int)($options['repeats'] ?? 7);
$warmup = (int)($options['warmup'] ?? 2_000);

$allSubjects = [
    'ruta' => fn() => new RutaSubject(),
    'ruta-cached' => fn() => new RutaCachedSubject(),
    'fastroute' => fn() => new FastRouteSubject(),
    'fastroute-cached' => fn() => new FastRouteCachedSubject(),
    'symfony' => fn() => new SymfonySubject(),
    'symfony-compiled' => fn() => new SymfonyCompiledSubject(),
];

$subjectKeys = $csv($options['subjects'] ?? implode(',', array_keys($allSubjects)));
$subjects = [];
foreach ($subjectKeys as $key) {
    if (!isset($allSubjects[$key])) {
        fwrite(STDERR, "Unknown subject '$key'. Available: " . implode(', ', array_keys($allSubjects)) . "\n");
        exit(1);
    }
    $subjects[] = $allSubjects[$key]();
}

printf(
    "## Router benchmark\n\nPHP %s, opcache %s | subjects: %s\n",
    PHP_VERSION,
    function_exists('opcache_get_status') && (opcache_get_status(false)['opcache_enabled'] ?? false) ? 'on' : 'off',
    implode(', ', array_map(fn($s) => $s->name(), $subjects)),
);

$cacheRoot = __DIR__ . '/var/' . getmypid();
$generator = new RouteSetGenerator();
$runner = new Runner($subjects, $iterations, $repeats, $warmup);

try {
    foreach ($shapes as $shape) {
        foreach ($routeCounts as $count) {
            $cacheDir = "$cacheRoot/$shape-$count";
            $runner->run($generator->generate($shape, $count), $cacheDir);
        }
    }
} finally {
    // best-effort cleanup of this run's cache files
    $files = glob("$cacheRoot/*/*/*") ?: [];
    foreach ($files as $file) {
        @unlink($file);
    }
    foreach (glob("$cacheRoot/*/*") ?: [] as $dir) {
        @rmdir($dir);
    }
    foreach (glob("$cacheRoot/*") ?: [] as $dir) {
        @rmdir($dir);
    }
    @rmdir($cacheRoot);
}
