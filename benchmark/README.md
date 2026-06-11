# Router benchmark

Re-runnable benchmark comparing [ruta](https://github.com/tylersriver/ruta) against
[nikic/fast-route](https://github.com/nikic/FastRoute) and
[symfony/routing](https://github.com/symfony/routing) under various configurations.

## Running it

The benchmark is a standalone composer project so the comparison libraries never
become dependencies of ruta itself (ruta is pulled in through a `path` repository):

```bash
cd benchmark
composer install
php run.php
```

It can also be run on demand from GitHub via the manually triggered
**Benchmark** workflow (`Actions → Benchmark → Run workflow`), which prints the
result tables to the job summary.

For numbers closer to a production web request, enable opcache for the CLI run:

```bash
php -d opcache.enable_cli=1 run.php
```

### Options

```text
--routes=60,600,1200   comma separated route-set sizes
--shapes=rest,static   route-set shapes to benchmark
--subjects=...         ruta, ruta-cached, fastroute, fastroute-cached,
                       symfony, symfony-compiled (default: all)
--iterations=20000     dispatches per measurement
--repeats=7            measurements per cell (median reported)
--warmup=2000          warmup dispatches before measuring
```

## What is measured

Route sets are generated deterministically, so every router and every run sees
exactly the same routes and requests. Two shapes are generated:

- **rest** — a typical versioned API: 6 routes per resource under `/api/v1`
  (`GET|POST /api/v1/{res}`, `GET|PUT|DELETE /api/v1/{res}/:id`,
  `GET /api/v1/{res}/:id/history`), mixing static and dynamic segments.
- **static** — purely static three-segment documentation style routes.

Subjects (one column block per scenario, values are the median over the repeats):

| subject              | what it is                                                          |
|----------------------|---------------------------------------------------------------------|
| `ruta`               | `Ruta\Router`, routes registered in memory                          |
| `ruta (cached)`      | `Ruta\cachedRouter()` loading the route map from its cache file     |
| `fastroute`          | `FastRoute\simpleDispatcher` (GroupCountBased)                      |
| `fastroute (cached)` | `FastRoute\cachedDispatcher` loading from its cache file            |
| `symfony`            | `Symfony\Component\Routing\Matcher\UrlMatcher` over a RouteCollection |
| `symfony (compiled)` | `CompiledUrlMatcher` loading a dumped, compiled matcher from a file |

Metrics:

- **setup** — time to get a dispatch-ready router: full route registration for
  the in-memory subjects, loading the warm cache file for the cached/compiled
  subjects. This is paid once per request in a classic PHP-FPM setup.
- **dispatch scenarios** — time per single dispatch, cycling over requests that
  hit the first, middle and last registered resource: a static path, a dynamic
  path (`/api/v1/users/123`), a deeper dynamic path
  (`/api/v1/users/123/history`) and misses (`not found`).

Fairness notes:

- All subjects route to plain string handler identifiers (no closures), since
  the cached subjects cannot serialize closures.
- One PSR-7 `ServerRequest` per request is pre-built outside the timed loops;
  ruta dispatches the request object, FastRoute/Symfony dispatch the
  method + path strings, which is how each library is used in practice.
- Symfony's matcher signals a miss by throwing, so its `not found` numbers
  include the cost of throwing/catching — that is its real-world behaviour.
- Before measuring, every subject is verified to actually match the hit
  scenarios and miss the not-found scenarios.

## Results

Run on PHP 8.4.19 (CLI, opcache off), Linux container, defaults
(`20000 iterations × 7 repeats`, medians). Re-run locally for numbers on your
hardware; relative ordering is what matters.

### Shape `rest` — 60 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | dynamic (hit) µs/op | dynamic deep (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|---------------------:|--------------------------:|-----------------:|
| ruta               |      65.8 |                1.17 |                 1.55 |                      1.64 |             1.13 |
| ruta (cached)      |      35.4 |                1.16 |                 1.64 |                      1.64 |             1.18 |
| fastroute          |       133 |                0.21 |                 0.80 |                      0.84 |             1.23 |
| fastroute (cached) |      55.8 |                0.22 |                 0.82 |                      0.86 |             1.27 |
| symfony            |      79.5 |                6.88 |                 8.26 |                      9.21 |             11.8 |
| symfony (compiled) |       139 |                0.58 |                 1.10 |                      1.13 |             1.29 |

### Shape `rest` — 600 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | dynamic (hit) µs/op | dynamic deep (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|---------------------:|--------------------------:|-----------------:|
| ruta               |       660 |                1.84 |                 2.29 |                      2.40 |             2.37 |
| ruta (cached)      |       269 |                1.67 |                 2.15 |                      2.19 |             2.28 |
| fastroute          |     2,698 |                0.23 |                 2.45 |                      2.48 |             7.03 |
| fastroute (cached) |       530 |                0.24 |                 2.45 |                      2.49 |             7.15 |
| symfony            |       883 |                53.7 |                 55.0 |                      55.8 |             99.3 |
| symfony (compiled) |     1,409 |                0.59 |                 1.38 |                      1.46 |             1.59 |

### Shape `rest` — 1200 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | dynamic (hit) µs/op | dynamic deep (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|---------------------:|--------------------------:|-----------------:|
| ruta               |     1,399 |                3.16 |                 3.59 |                      3.62 |             3.91 |
| ruta (cached)      |       564 |                2.32 |                 2.81 |                      2.94 |             3.63 |
| fastroute          |     8,526 |                0.22 |                 4.31 |                      4.35 |             14.0 |
| fastroute (cached) |     1,144 |                0.23 |                 4.27 |                      4.30 |             13.7 |
| symfony            |     1,809 |                 105 |                  106 |                       108 |              197 |
| symfony (compiled) |     2,970 |                0.57 |                 1.69 |                      1.69 |             1.88 |

### Shape `static` — 60 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|-----------------:|
| ruta               |      57.5 |                1.20 |             1.06 |
| ruta (cached)      |      25.3 |                1.17 |             1.06 |
| fastroute          |      58.9 |                0.22 |             0.27 |
| fastroute (cached) |      24.5 |                0.23 |             0.28 |
| symfony            |      77.8 |                7.22 |             11.0 |
| symfony (compiled) |       128 |                0.59 |             1.12 |

### Shape `static` — 600 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|-----------------:|
| ruta               |       589 |                1.47 |             1.09 |
| ruta (cached)      |       135 |                1.36 |             1.06 |
| fastroute          |       638 |                0.21 |             0.27 |
| fastroute (cached) |       151 |                0.22 |             0.28 |
| symfony            |       925 |                54.3 |             99.3 |
| symfony (compiled) |     1,490 |                0.60 |             1.19 |

### Shape `static` — 1200 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|-----------------:|
| ruta               |     1,255 |                1.88 |             1.10 |
| ruta (cached)      |       278 |                1.50 |             1.06 |
| fastroute          |     1,353 |                0.21 |             0.27 |
| fastroute (cached) |       315 |                0.22 |             0.27 |
| symfony            |     1,860 |                 111 |              202 |
| symfony (compiled) |     3,039 |                0.60 |             1.13 |

### Interpretation

- **Dispatch:** ruta's segment-tree lookup depends on URI depth rather than on
  route count, so it degrades only mildly as the route set grows (≈1.2 µs at 60
  routes to ≈3 µs at 1200 in the rest shape, ≈1.8 µs purely static). FastRoute
  is unbeatable on static hits (a flat ≈0.2 µs hash lookup at any size), but its
  dynamic matching and especially its misses degrade with route count: from 600
  REST routes upward ruta dispatches dynamic hits about as fast or faster
  (2.3 µs vs 2.5 µs at 600; 3.6 µs vs 4.3 µs at 1200) and misses 3–4× faster
  (3.9 µs vs ~14 µs at 1200). Symfony's non-compiled `UrlMatcher` degrades
  linearly and is 30–60× slower than everything else at scale; its compiled
  matcher is the strongest all-rounder on big route sets (≈0.6 µs static,
  ≈1.7 µs dynamic at 1200 routes).
- **Setup:** ruta has the cheapest cold registration of all subjects (1.4 ms
  for 1200 REST routes vs 1.8 ms for Symfony's collection and 8.5 ms for
  FastRoute, whose regex compilation dominates). Caching pays off as the set
  grows: `Ruta\cachedRouter()` cuts setup roughly 2.5× at 600+ routes
  (660 µs → 269 µs, 1 399 µs → 564 µs). The Symfony compiled numbers include
  `require`-ing a large dumped file with opcache off; with opcache enabled
  (as in production) that include — and ruta's cache include — is essentially
  free.
- **Misses:** ruta rejects unknown paths in ~1 µs flat on static sets — its
  cheapest operation — while FastRoute's and Symfony's miss cost grows with
  route count (Symfony also pays for an exception per miss).
