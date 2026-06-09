<?php

namespace Ruta\Benchmark;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Ruta\Benchmark\Subject\Subject;

final class Runner
{
    /**
     * @param Subject[] $subjects
     */
    public function __construct(
        private readonly array $subjects,
        private readonly int $iterations,
        private readonly int $repeats,
        private readonly int $warmup,
    ) {
    }

    public function run(RouteSet $set, string $cacheDir): void
    {
        $requests = $this->buildRequests($set);

        printf(
            "\n### Shape `%s` — %d routes (%d iterations × %d repeats, median)\n\n",
            $set->shape,
            $set->count(),
            $this->iterations,
            $this->repeats,
        );

        $scenarioNames = array_keys($set->scenarios);
        $header = array_merge(['router', 'setup µs'], array_map(fn($s) => "$s µs/op", $scenarioNames));
        $rows = [];

        foreach ($this->subjects as $subject) {
            $subjectCacheDir = $cacheDir . '/' . preg_replace('/[^\w-]+/', '-', $subject->name());
            if (!is_dir($subjectCacheDir) && !mkdir($subjectCacheDir, 0775, true)) {
                throw new RuntimeException("Unable to create cache dir $subjectCacheDir");
            }

            $subject->prepare($set, $subjectCacheDir);
            $this->verify($subject, $set, $requests);

            $row = [$subject->name(), self::formatMicros($this->measureSetup($subject))];
            foreach ($set->scenarios as $pairs) {
                $row[] = self::formatMicros($this->measureDispatch($subject, $pairs, $requests));
            }
            $rows[] = $row;
        }

        $this->printTable($header, $rows);
    }

    /**
     * Pre-build one PSR-7 request per scenario request so request object
     * construction is excluded from the timings of every subject.
     *
     * @return array<string, ServerRequestInterface>
     */
    private function buildRequests(RouteSet $set): array
    {
        $requests = [];
        foreach ($set->scenarios as $pairs) {
            foreach ($pairs as [$method, $path]) {
                $requests["$method $path"] = new ServerRequest($method, $path);
            }
        }

        return $requests;
    }

    /**
     * Sanity-check that the subject actually matches (or misses) what the
     * scenario expects before we spend time measuring it.
     *
     * @param array<string, ServerRequestInterface> $requests
     */
    private function verify(Subject $subject, RouteSet $set, array $requests): void
    {
        foreach ($set->scenarios as $scenario => $pairs) {
            $expected = !str_contains($scenario, 'not found');
            foreach ($pairs as [$method, $path]) {
                $matched = $subject->dispatch($method, $path, $requests["$method $path"]);
                if ($matched !== $expected) {
                    throw new RuntimeException(sprintf(
                        '%s: expected %s for "%s %s" (scenario "%s") but got the opposite',
                        $subject->name(),
                        $expected ? 'a match' : 'a miss',
                        $method,
                        $path,
                        $scenario,
                    ));
                }
            }
        }
    }

    /**
     * @return float median microseconds for one build
     */
    private function measureSetup(Subject $subject): float
    {
        $times = [];
        for ($r = 0; $r < $this->repeats; $r++) {
            $start = hrtime(true);
            $subject->build();
            $times[] = (hrtime(true) - $start) / 1_000;
        }

        return self::median($times);
    }

    /**
     * @param array<int, array{string, string}>      $pairs
     * @param array<string, ServerRequestInterface> $requests
     * @return float median microseconds per dispatch
     */
    private function measureDispatch(Subject $subject, array $pairs, array $requests): float
    {
        $calls = [];
        foreach ($pairs as [$method, $path]) {
            $calls[] = [$method, $path, $requests["$method $path"]];
        }
        $count = count($calls);

        for ($i = 0; $i < $this->warmup; $i++) {
            [$method, $path, $request] = $calls[$i % $count];
            $subject->dispatch($method, $path, $request);
        }

        $times = [];
        for ($r = 0; $r < $this->repeats; $r++) {
            $start = hrtime(true);
            for ($i = 0; $i < $this->iterations; $i++) {
                [$method, $path, $request] = $calls[$i % $count];
                $subject->dispatch($method, $path, $request);
            }
            $times[] = (hrtime(true) - $start) / 1_000 / $this->iterations;
        }

        return self::median($times);
    }

    /**
     * @param float[] $values
     */
    private static function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    private static function formatMicros(float $micros): string
    {
        return $micros >= 100
            ? number_format($micros, 0)
            : number_format($micros, $micros >= 10 ? 1 : 2);
    }

    /**
     * @param string[]                 $header
     * @param array<int, string[]>     $rows
     */
    private function printTable(array $header, array $rows): void
    {
        $widths = array_map('strlen', $header);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        $line = fn(array $cells) => '| ' . implode(' | ', array_map(
            fn(string $cell, int $i) => str_pad($cell, $widths[$i], ' ', $i === 0 ? STR_PAD_RIGHT : STR_PAD_LEFT),
            $cells,
            array_keys($cells),
        )) . " |\n";

        echo $line($header);
        echo '|' . implode('|', array_map(
            fn(int $w, int $i) => $i === 0 ? str_repeat('-', $w + 2) : str_repeat('-', $w + 1) . ':',
            $widths,
            array_keys($widths),
        )) . "|\n";
        foreach ($rows as $row) {
            echo $line($row);
        }
    }
}
