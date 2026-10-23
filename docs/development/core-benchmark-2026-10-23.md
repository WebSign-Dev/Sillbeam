# Core v0.2.0 paired Symfony benchmark — 2026-10-23

This measures the cost of Sillbeam's existing build validation and runtime integration against native Symfony providing the same application services/routes/responses. It does **not** establish a general performance advantage. Sillbeam adds architectural rules/reporting that the native application does not provide; the baseline is functionally equivalent for the exercised HTTP/DI workload, not an alternative architecture validator.

## Reproduction and workload

```bash
php tools/benchmark-architecture.php > benchmark.jsonl
php tools/benchmark-architecture.php --smoke
php tools/benchmark-architecture.php --phases > phases.jsonl
```

[Raw paired samples and machine metadata](benchmarks/core-0.2.0-2026-10-23.jsonl) contain every measured duration plus nearest-rank p50/p95/min/max and PHP allocated memory peaks. The benchmark JSONL format is version 2; it is separate from architecture-report JSON schema version 1. `--phases` preserves the old standalone phase diagnostic with its original row format; those preparsed measurements must not be mixed with the paired build comparison.

Each of 10/50/100 logical modules has 20 services, one descriptor and one controller/route (220/1,100/2,200 module PHP files, plus the kernel). All 20 service types form chains through the previous module. Every controller receives its 20 services. Requests hit the last module and traverse all chains, returning exactly `ok:200`, `ok:1000` or `ok:2000`. The tool rejects mismatched routes, responses or production analysis loads. The native application uses ordinary Symfony service imports and explicit attribute route imports; descriptor placeholders have no Sillbeam attributes and are excluded from services. It never registers/loads SillbeamCoreBundle. Both use the same Composer autoloader/dependency set, FrameworkBundle and Symfony Kernel/MicroKernelTrait; the native process does not load Sillbeam's analyzer or PHP parser. The benchmark excludes the optional SillbeamKernel, Projects, Doctrine, database work and disabled modules; their behavior is covered by RC tests rather than these timings.

For every size/environment, three paired repetitions use newly generated source/cache workspaces. Pair order alternates between repetitions. Each cold build, cached build, warm boot and HTTP batch runs in a separate PHP process. No architecture phase is executed before the cold build.

- **Cold build:** time for the ordinary Symfony Console Application to run `cache:warmup` with an empty Symfony cache, including container compilation, standard warmers and Sillbeam preflight. No OS cache flush.
- **Cached build:** the same command in a new process with the preceding valid cache. Sillbeam still performs fresh architecture validation. This is distinct from merely booting a cached container.
- **Warm boot:** a new process boots the already built container, including debug resource checks when enabled.
- **First HTTP:** first `Kernel::handle()` in a fresh process, with the production/development cache already built. Includes container boot and class loading, not process startup/vendor-autoloader setup.
- **Warm HTTP:** 50 more requests per repeat, 150 samples total, with a new kernel/container instance for each request but shared PHP class/process state within each batch. Timing includes `Kernel::handle()`; request construction, termination, shutdown, network/FPM/browser time are excluded. These are application-side HTTP latencies, not end-to-end network measurements. Symfony's standard debug behavior/logging is retained.
- **Memory:** maximum `memory_get_peak_usage(true)` across repeats in MiB. Reset at operation start; includes loaded PHP code/base allocations. HTTP peak covers the entire batch including its first request, not an individual-request delta; this is not RSS. Memory is not summed across processes.

Run: PHP 8.5.4, Symfony FrameworkBundle 8.1.6 / patched HttpFoundation 8.1.9, Linux x86_64, AMD FX-8350 Eight-Core Processor; CLI OPcache off, JIT disabled, memory limit unlimited. Source/caches are local temporary files. No test suites/quality jobs were run concurrently with the paired benchmark; the workstation and OS cache were not otherwise isolated. Tool SHA-256: `000938bd09574336edb58a19fe0214c7f1dbd76622e8b4280eb944dde2e5ae2e` (also recorded in the raw metadata). Root lockfile SHA-256: `3d036ace3138320519cd34494a47d6f8a829f504d4be87635918c3f2dd4eb4c6`. Measurements were repeated after the HttpFoundation security patch; earlier measurements were replaced. Results use milliseconds unless marked MiB.

## Observed build/boot medians

Three samples per cell; the raw data also reports p95, which with n=3 is simply the slowest observation. This small sample size supports descriptive timings, not significance claims.

| Modules | Env | Variant | Cold build p50 | Cached build p50 | Warm boot p50 | Cold build peak MiB |
| ---: | --- | --- | ---: | ---: | ---: | ---: |
| 10 | dev | symfony | 433.94 | 66.57 | 26.80 | 26 |
| 10 | dev | sillbeam | 762.57 | 259.82 | 33.10 | 28 |
| 10 | prod | symfony | 353.65 | 56.31 | 18.38 | 24 |
| 10 | prod | sillbeam | 653.87 | 235.73 | 19.29 | 26 |
| 50 | dev | symfony | 1075.02 | 91.76 | 50.47 | 42 |
| 50 | dev | sillbeam | 2601.66 | 783.59 | 70.09 | 50 |
| 50 | prod | symfony | 894.86 | 60.94 | 19.80 | 40 |
| 50 | prod | sillbeam | 2309.54 | 705.40 | 20.13 | 40 |
| 100 | dev | symfony | 1944.79 | 121.66 | 78.73 | 66 |
| 100 | dev | sillbeam | 5045.22 | 1453.89 | 115.12 | 76 |
| 100 | prod | symfony | 1589.24 | 57.88 | 18.41 | 62 |
| 100 | prod | sillbeam | 4409.46 | 1285.34 | 19.10 | 60 |

## Observed HTTP timings

First HTTP is the median of 3 independent process-first requests. Warm p50/p95 use 150 subsequent samples per row.

| Modules | Env | Variant | First HTTP p50 | Warm HTTP p50 | Warm HTTP p95 | HTTP batch peak MiB |
| ---: | --- | --- | ---: | ---: | ---: | ---: |
| 10 | dev | symfony | 53.27 | 1.959 | 2.149 | 12 |
| 10 | dev | sillbeam | 58.46 | 2.328 | 2.691 | 12 |
| 10 | prod | symfony | 40.64 | 0.716 | 0.907 | 10 |
| 10 | prod | sillbeam | 42.15 | 0.713 | 0.890 | 10 |
| 50 | dev | symfony | 140.30 | 4.077 | 5.782 | 20 |
| 50 | dev | sillbeam | 189.20 | 6.453 | 8.507 | 22 |
| 50 | prod | symfony | 117.48 | 1.627 | 3.321 | 18 |
| 50 | prod | sillbeam | 113.27 | 1.488 | 3.210 | 18 |
| 100 | dev | symfony | 252.61 | 7.691 | 9.757 | 32 |
| 100 | dev | sillbeam | 350.47 | 12.277 | 14.306 | 34 |
| 100 | prod | symfony | 197.37 | 3.397 | 5.052 | 28 |
| 100 | prod | sillbeam | 201.26 | 3.404 | 5.031 | 28 |

## Interpretation and limits

The measured Sillbeam cold/cached builds cost more than the native baseline at every tested size/environment. This is expected additional work from discovery, source analysis, graph validation, automatic integration and the fresh build preflight; the benchmark does not attribute the entire delta to any single phase. At 100 modules in production, cold build medians are 1,589.24 ms (native) and 4,409.46 ms (Sillbeam), a measured additional 2,820.21 ms in this run (difference calculated before rounding). Avoid interpreting cached `cache:warmup` as a cheap production request: it deliberately validates again.

Warm production HTTP p50/p95 remain in the same millisecond range and the relative difference changes with size. The table preserves the measured differences rather than treating small changes as evidence of a general speed advantage. Analyzer and PHP parser were absent from every production HTTP subprocess in both variants; this is a direct load probe, not an inference from close timings. Production cold HTTP without prebuilt cache is tested separately to return generic 503; it is not included as a successful-request latency.

Debug costs grow with the source graph/resource checks. The 100-module debug warm HTTP median is higher for Sillbeam; the raw timings retain outliers instead of hiding them. Memory peaks include allocation buckets and cannot reliably attribute small differences; raw records include separate cached-build and boot peaks as well.

This synthetic linear dependency graph, single endpoint, three build repeats, one workstation/PHP/Symfony version and in-process sequential requests do not represent arbitrary Project layouts, Doctrine/database work, network traffic, FPM process startup, concurrent load or enabled production OPcache/JIT. Results from the old phase-only benchmark used a different workload and partially warmed process and are not a valid before/after performance comparison. Re-run on the intended deployment with realistic workload before making capacity or performance claims.
