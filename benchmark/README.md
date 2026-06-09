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
| ruta               |      69.7 |                1.16 |                 1.56 |                      1.63 |             1.13 |
| ruta (cached)      |      34.8 |                1.16 |                 1.56 |                      1.63 |             1.12 |
| fastroute          |       127 |                0.21 |                 0.83 |                      0.87 |             1.24 |
| fastroute (cached) |      59.1 |                0.22 |                 0.82 |                      0.85 |             1.29 |
| symfony            |      80.9 |                6.68 |                 7.85 |                      9.03 |             11.4 |
| symfony (compiled) |       129 |                0.58 |                 1.10 |                      1.12 |             1.35 |

### Shape `rest` — 600 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | dynamic (hit) µs/op | dynamic deep (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|---------------------:|--------------------------:|-----------------:|
| ruta               |       724 |                1.84 |                 2.29 |                      2.42 |             2.16 |
| ruta (cached)      |       265 |                1.72 |                 2.10 |                      2.16 |             2.07 |
| fastroute          |     2,721 |                0.23 |                 2.37 |                      2.42 |             6.94 |
| fastroute (cached) |       544 |                0.23 |                 2.44 |                      2.47 |             7.02 |
| symfony            |       884 |                51.9 |                 53.2 |                      54.6 |             97.0 |
| symfony (compiled) |     1,389 |                0.59 |                 1.37 |                      1.42 |             1.61 |

### Shape `rest` — 1200 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | dynamic (hit) µs/op | dynamic deep (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|---------------------:|--------------------------:|-----------------:|
| ruta               |     1,484 |                3.01 |                 3.43 |                      3.53 |             3.50 |
| ruta (cached)      |       557 |                2.52 |                 2.98 |                      3.10 |             3.32 |
| fastroute          |     9,070 |                0.22 |                 4.40 |                      4.40 |             13.9 |
| fastroute (cached) |     1,142 |                0.22 |                 4.35 |                      4.41 |             14.2 |
| symfony            |     1,828 |                 104 |                  108 |                       106 |              197 |
| symfony (compiled) |     2,795 |                0.59 |                 1.68 |                      1.71 |             1.93 |

### Shape `static` — 60 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|-----------------:|
| ruta               |      68.2 |                1.25 |             1.04 |
| ruta (cached)      |      24.9 |                1.21 |             1.08 |
| fastroute          |      59.1 |                0.23 |             0.28 |
| fastroute (cached) |      20.4 |                0.23 |             0.27 |
| symfony            |      78.0 |                7.42 |             11.2 |
| symfony (compiled) |       125 |                0.59 |             1.15 |

### Shape `static` — 600 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|-----------------:|
| ruta               |       633 |                1.57 |             1.01 |
| ruta (cached)      |       137 |                1.32 |             1.00 |
| fastroute          |       624 |                0.21 |             0.27 |
| fastroute (cached) |       150 |                0.21 |             0.27 |
| symfony            |       936 |                54.9 |             99.7 |
| symfony (compiled) |     1,429 |                0.59 |             1.15 |

### Shape `static` — 1200 routes (20000 iterations × 7 repeats, median)

| router             | setup µs | static (hit) µs/op | not found µs/op |
|--------------------|----------:|--------------------:|-----------------:|
| ruta               |     1,332 |                1.76 |             1.00 |
| ruta (cached)      |       259 |                1.50 |             1.01 |
| fastroute          |     1,319 |                0.21 |             0.27 |
| fastroute (cached) |       314 |                0.22 |             0.28 |
| symfony            |     2,246 |                 111 |              205 |
| symfony (compiled) |     2,994 |                0.60 |             1.16 |

### Interpretation

- **Dispatch:** ruta's segment-tree lookup depends on URI depth rather than on
  route count, so it degrades only mildly as the route set grows (≈1.2 µs at 60
  routes to ≈3 µs at 1200 in the rest shape, ≈1.8 µs purely static). FastRoute
  is unbeatable on static hits (a flat ≈0.2 µs hash lookup at any size), but its
  dynamic matching and especially its misses degrade with route count: from 600
  REST routes upward ruta dispatches dynamic hits about as fast or faster
  (2.3 µs vs 2.4 µs at 600; 3.4 µs vs 4.4 µs at 1200) and misses 3–4× faster
  (3.5 µs vs ~14 µs at 1200). Symfony's non-compiled `UrlMatcher` degrades
  linearly and is 30–60× slower than everything else at scale; its compiled
  matcher is the strongest all-rounder on big route sets (≈0.6 µs static,
  ≈1.7 µs dynamic at 1200 routes).
- **Setup:** ruta has the cheapest cold registration of all subjects (1.5 ms
  for 1200 REST routes vs 1.8 ms for Symfony's collection and 9.1 ms for
  FastRoute, whose regex compilation dominates). Caching pays off as the set
  grows: `Ruta\cachedRouter()` cuts setup roughly 3× at 600+ routes
  (724 µs → 265 µs, 1 484 µs → 557 µs). The Symfony compiled numbers include
  `require`-ing a large dumped file with opcache off; with opcache enabled
  (as in production) that include — and ruta's cache include — is essentially
  free.
- **Misses:** ruta rejects unknown paths in ~1 µs flat on static sets — its
  cheapest operation — while FastRoute's and Symfony's miss cost grows with
  route count (Symfony also pays for an exception per miss).
