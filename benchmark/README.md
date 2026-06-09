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

<!-- RESULTS -->

### Interpretation

- **Dispatch:** ruta's segment-tree lookup is essentially O(depth of the URI),
  so its dispatch time is flat regardless of how many routes are registered —
  at 1200 routes it dispatches static hits faster than FastRoute and an order
  of magnitude faster than Symfony's non-compiled matcher. FastRoute stays
  fastest on small static sets; its dynamic dispatch degrades mildly with route
  count (chunked regexes), while Symfony's `UrlMatcher` degrades linearly and
  its compiled matcher stays competitive.
- **Setup:** registration cost grows with route count for every in-memory
  subject. The cached subjects pay a near-constant file include instead, which
  is the point of `Ruta\cachedRouter()`: at 1200 routes the cached setup is
  several times cheaper than re-registering, and with opcache the include is
  effectively free.
- **Misses:** ruta and FastRoute reject unknown paths cheaply; Symfony pays for
  an exception per miss.
