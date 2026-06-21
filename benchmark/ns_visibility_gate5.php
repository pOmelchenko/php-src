<?php

declare(strict_types=1);

const NSVIS_DEFAULT_RUNS = 7;
const NSVIS_DEFAULT_WARMUP = 2;
const NSVIS_DEFAULT_ITERS = 1000000;
const NSVIS_DEFAULT_DENIED_ITERS = 10000;
const NSVIS_DEFAULT_AUTOLOAD_ITERS = 20000;
const NSVIS_DEFAULT_MEMORY_CLASSES = 10000;

main($argv);

function main(array $argv): void
{
    $options = parse_options($argv);

    if (isset($options['worker'])) {
        worker_main($options);
        return;
    }

    if (isset($options['memory-worker'])) {
        memory_worker_main($options);
        return;
    }

    if (isset($options['probe-namespace-visibility'])) {
        echo json_encode(['supported' => supports_namespace_visibility()], JSON_PRETTY_PRINT) . "\n";
        return;
    }

    coordinator_main($options);
}

function coordinator_main(array $options): void
{
    $runs = env_int('NSVIS_RUNS', NSVIS_DEFAULT_RUNS);
    $warmup = env_non_negative_int('NSVIS_WARMUP', NSVIS_DEFAULT_WARMUP);
    $iterations = env_int('NSVIS_ITERS', NSVIS_DEFAULT_ITERS);
    $deniedIterations = env_int('NSVIS_DENIED_ITERS', NSVIS_DEFAULT_DENIED_ITERS);
    $autoloadIterations = env_int('NSVIS_AUTOLOAD_ITERS', min($iterations, NSVIS_DEFAULT_AUTOLOAD_ITERS));
    $memoryClasses = env_int('NSVIS_MEMORY_CLASSES', NSVIS_DEFAULT_MEMORY_CLASSES);
    $php = $options['php'] ?? PHP_BINARY;

    $namespaceVisibility = probe_namespace_visibility_support($php);
    $cases = public_cases($iterations, $autoloadIterations);
    if ($namespaceVisibility) {
        $cases = array_merge($cases, restricted_cases($iterations, $deniedIterations));
    }

    $result = [
        'schema' => 'ns-visibility-gate5-v1',
        'generated_at' => gmdate('c'),
        'php_binary' => $php,
        'commit' => trim((string) run_command(['git', 'rev-parse', 'HEAD'], dirname(__DIR__))['stdout']),
        'environment' => [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'php_uname' => php_uname(),
            'os_family' => PHP_OS_FAMILY,
            'machine' => php_uname('m'),
        ],
        'configuration' => [
            'runs' => $runs,
            'warmup' => $warmup,
            'iterations' => $iterations,
            'denied_iterations' => $deniedIterations,
            'autoload_iterations' => $autoloadIterations,
            'memory_classes' => $memoryClasses,
            'namespace_visibility_supported' => $namespaceVisibility,
        ],
        'modes' => [],
        'opcache_memory' => [],
    ];

    foreach (opcache_modes() as $mode => $ini) {
        $result['modes'][$mode] = [
            'ini' => $ini,
            'cases' => [],
        ];

        foreach ($cases as $caseName => $case) {
            $result['modes'][$mode]['cases'][$caseName] = run_worker(
                $php,
                $ini,
                $caseName,
                $case['iterations'],
                $runs,
                $warmup,
                $mode
            );
        }
    }

    $memoryKinds = ['public'];
    if ($namespaceVisibility) {
        $memoryKinds[] = 'private_restricted';
        $memoryKinds[] = 'protected_restricted';
    }
    foreach ($memoryKinds as $kind) {
        $result['opcache_memory'][$kind] = run_memory_worker(
            $php,
            opcache_modes()['opcache_no_jit'],
            $kind,
            $memoryClasses
        );
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function public_cases(int $iterations, int $autoloadIterations): array
{
    return [
        'public_new' => ['iterations' => $iterations],
        'public_static_method' => ['iterations' => $iterations],
        'public_static_property' => ['iterations' => $iterations],
        'public_class_constant' => ['iterations' => $iterations],
        'public_instanceof' => ['iterations' => $iterations],
        'public_dynamic_lookup_warm' => ['iterations' => $iterations],
        'public_callable_validation' => ['iterations' => $iterations],
        'public_autoload_hit' => ['iterations' => $autoloadIterations],
    ];
}

function restricted_cases(int $iterations, int $deniedIterations): array
{
    return [
        'private_allowed_new' => ['iterations' => $iterations],
        'private_allowed_static_method' => ['iterations' => $iterations],
        'private_allowed_static_property' => ['iterations' => $iterations],
        'private_allowed_class_constant' => ['iterations' => $iterations],
        'private_allowed_instanceof' => ['iterations' => $iterations],
        'private_allowed_callable_validation' => ['iterations' => $iterations],
        'protected_allowed_child_new' => ['iterations' => $iterations],
        'protected_allowed_child_static_method' => ['iterations' => $iterations],
        'protected_allowed_child_instanceof' => ['iterations' => $iterations],
        'private_denied_new' => ['iterations' => $deniedIterations],
        'private_denied_static_method' => ['iterations' => $deniedIterations],
        'private_denied_instanceof' => ['iterations' => $deniedIterations],
        'protected_denied_new' => ['iterations' => $deniedIterations],
    ];
}

function opcache_modes(): array
{
    return [
        'no_opcache' => [
            'opcache.enable_cli=0',
            'opcache.jit=disable',
            'opcache.jit_buffer_size=0',
        ],
        'opcache_no_jit' => [
            'opcache.enable_cli=1',
            'opcache.validate_timestamps=0',
            'opcache.file_update_protection=0',
            'opcache.jit=disable',
            'opcache.jit_buffer_size=0',
        ],
        'opcache_function_jit' => [
            'opcache.enable_cli=1',
            'opcache.validate_timestamps=0',
            'opcache.file_update_protection=0',
            'opcache.jit=function',
            'opcache.jit_buffer_size=128M',
            'opcache.jit_hot_func=1',
            'opcache.jit_hot_loop=1',
            'opcache.jit_hot_return=1',
            'opcache.jit_hot_side_exit=1',
        ],
        'opcache_tracing_jit' => [
            'opcache.enable_cli=1',
            'opcache.validate_timestamps=0',
            'opcache.file_update_protection=0',
            'opcache.jit=1255',
            'opcache.jit_buffer_size=128M',
            'opcache.jit_hot_func=1',
            'opcache.jit_hot_loop=1',
            'opcache.jit_hot_return=1',
            'opcache.jit_hot_side_exit=1',
        ],
    ];
}

function run_worker(
    string $php,
    array $ini,
    string $caseName,
    int $iterations,
    int $runs,
    int $warmup,
    string $mode
): array {
    $args = [
        __FILE__,
        '--worker',
        '--case=' . $caseName,
        '--iterations=' . (string) $iterations,
        '--runs=' . (string) $runs,
        '--warmup=' . (string) $warmup,
        '--mode=' . $mode,
    ];
    return run_php_json($php, $ini, $args);
}

function run_memory_worker(string $php, array $ini, string $kind, int $classes): array
{
    $args = [
        __FILE__,
        '--memory-worker',
        '--kind=' . $kind,
        '--classes=' . (string) $classes,
    ];
    return run_php_json($php, $ini, $args);
}

function run_php_json(string $php, array $ini, array $args): array
{
    $command = [$php];
    foreach ($ini as $setting) {
        $command[] = '-d';
        $command[] = $setting;
    }
    array_push($command, ...$args);

    $process = run_command($command, dirname(__DIR__));
    $decoded = json_decode($process['stdout'], true);
    if (!is_array($decoded)) {
        fwrite(STDERR, $process['stdout']);
        fwrite(STDERR, $process['stderr']);
        throw new RuntimeException('Worker did not return JSON');
    }
    return $decoded;
}

function worker_main(array $options): void
{
    $caseName = require_option($options, 'case');
    $iterations = positive_int(require_option($options, 'iterations'), 'iterations');
    $runs = positive_int(require_option($options, 'runs'), 'runs');
    $warmup = non_negative_int(require_option($options, 'warmup'), 'warmup');
    $mode = $options['mode'] ?? 'unknown';

    load_public_fixture();
    if (case_uses_restricted_fixture($caseName)) {
        load_restricted_fixture();
    }

    $warmupSinks = [];
    for ($i = 0; $i < $warmup; $i++) {
        $warmupSinks[] = run_case($caseName, $iterations, $i);
    }

    $timings = [];
    $sinks = [];
    for ($i = 0; $i < $runs; $i++) {
        $runId = $warmup + $i;
        $start = hrtime(true);
        $sinks[] = run_case($caseName, $iterations, $runId);
        $timings[] = hrtime(true) - $start;
    }

    $median = median($timings);
    $result = [
        'case' => $caseName,
        'mode' => $mode,
        'iterations' => $iterations,
        'runs' => $runs,
        'warmup' => $warmup,
        'timings_ns' => $timings,
        'median_ns' => $median,
        'median_ns_per_iter' => $median / $iterations,
        'min_ns' => min($timings),
        'max_ns' => max($timings),
        'stddev_ns' => stddev($timings),
        'relative_stddev' => relative_stddev($timings),
        'sink' => end($sinks),
        'warmup_sink' => end($warmupSinks),
        'ini' => worker_ini_snapshot(),
    ];

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function memory_worker_main(array $options): void
{
    $kind = require_option($options, 'kind');
    $classes = positive_int(require_option($options, 'classes'), 'classes');

    if (!function_exists('opcache_get_status') || !function_exists('opcache_compile_file')) {
        echo json_encode([
            'kind' => $kind,
            'classes' => $classes,
            'skipped' => true,
            'reason' => 'OPcache extension is not loaded',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }

    if (!ini_bool('opcache.enable_cli')) {
        echo json_encode([
            'kind' => $kind,
            'classes' => $classes,
            'skipped' => true,
            'reason' => 'opcache.enable_cli is disabled',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }

    if ($kind !== 'public' && !supports_namespace_visibility()) {
        echo json_encode([
            'kind' => $kind,
            'classes' => $classes,
            'skipped' => true,
            'reason' => 'namespace visibility syntax is not supported',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }

    $file = write_temp_php_file(generate_memory_fixture($kind, $classes));

    $before = opcache_get_status(false);
    $compiled = @opcache_compile_file($file);
    $after = opcache_get_status(false);

    $beforeMemory = $before['memory_usage'] ?? [];
    $afterMemory = $after['memory_usage'] ?? [];
    $beforeInterned = $before['interned_strings_usage'] ?? [];
    $afterInterned = $after['interned_strings_usage'] ?? [];

    echo json_encode([
        'kind' => $kind,
        'classes' => $classes,
        'compiled' => $compiled,
        'file_bytes' => filesize($file),
        'used_memory_before' => $beforeMemory['used_memory'] ?? null,
        'used_memory_after' => $afterMemory['used_memory'] ?? null,
        'used_memory_delta' => memory_delta($beforeMemory, $afterMemory, 'used_memory'),
        'wasted_memory_delta' => memory_delta($beforeMemory, $afterMemory, 'wasted_memory'),
        'interned_used_memory_before' => $beforeInterned['used_memory'] ?? null,
        'interned_used_memory_after' => $afterInterned['used_memory'] ?? null,
        'interned_used_memory_delta' => memory_delta($beforeInterned, $afterInterned, 'used_memory'),
        'bytes_per_class' => memory_delta($beforeMemory, $afterMemory, 'used_memory') / $classes,
        'interned_bytes_per_class' => memory_delta($beforeInterned, $afterInterned, 'used_memory') / $classes,
        'ini' => worker_ini_snapshot(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function memory_delta(array $before, array $after, string $key): ?int
{
    if (!isset($before[$key], $after[$key])) {
        return null;
    }
    return (int) $after[$key] - (int) $before[$key];
}

function worker_ini_snapshot(): array
{
    return [
        'opcache.enable_cli' => ini_get('opcache.enable_cli'),
        'opcache.jit' => ini_get('opcache.jit'),
        'opcache.jit_buffer_size' => ini_get('opcache.jit_buffer_size'),
        'opcache.jit_hot_func' => ini_get('opcache.jit_hot_func'),
        'opcache.jit_hot_loop' => ini_get('opcache.jit_hot_loop'),
    ];
}

function run_case(string $caseName, int $iterations, int $runId): int
{
    return match ($caseName) {
        'public_new' => \NsVisGate5BenchPublic\public_new_loop($iterations),
        'public_static_method' => \NsVisGate5BenchPublic\public_static_method_loop($iterations),
        'public_static_property' => \NsVisGate5BenchPublic\public_static_property_loop($iterations),
        'public_class_constant' => \NsVisGate5BenchPublic\public_class_constant_loop($iterations),
        'public_instanceof' => \NsVisGate5BenchPublic\public_instanceof_loop($iterations),
        'public_dynamic_lookup_warm' => \NsVisGate5BenchPublic\public_dynamic_lookup_warm_loop($iterations),
        'public_callable_validation' => \NsVisGate5BenchPublic\public_callable_validation_loop($iterations),
        'public_autoload_hit' => public_autoload_hit_loop($iterations, $runId),
        'private_allowed_new' => \NsVisGate5BenchRestricted\private_allowed_new_loop($iterations),
        'private_allowed_static_method' => \NsVisGate5BenchRestricted\private_allowed_static_method_loop($iterations),
        'private_allowed_static_property' => \NsVisGate5BenchRestricted\private_allowed_static_property_loop($iterations),
        'private_allowed_class_constant' => \NsVisGate5BenchRestricted\private_allowed_class_constant_loop($iterations),
        'private_allowed_instanceof' => \NsVisGate5BenchRestricted\private_allowed_instanceof_loop($iterations),
        'private_allowed_callable_validation' => \NsVisGate5BenchRestricted\private_allowed_callable_validation_loop($iterations),
        'protected_allowed_child_new' => \NsVisGate5BenchRestricted\Child\protected_allowed_child_new_loop($iterations),
        'protected_allowed_child_static_method' => \NsVisGate5BenchRestricted\Child\protected_allowed_child_static_method_loop($iterations),
        'protected_allowed_child_instanceof' => \NsVisGate5BenchRestricted\Child\protected_allowed_child_instanceof_loop($iterations),
        'private_denied_new' => \NsVisGate5BenchRestricted\Consumer\private_denied_new_loop($iterations),
        'private_denied_static_method' => \NsVisGate5BenchRestricted\Consumer\private_denied_static_method_loop($iterations),
        'private_denied_instanceof' => \NsVisGate5BenchRestricted\Consumer\private_denied_instanceof_loop($iterations),
        'protected_denied_new' => \NsVisGate5BenchOutside\protected_denied_new_loop($iterations),
        default => throw new InvalidArgumentException('Unknown benchmark case: ' . $caseName),
    };
}

function case_uses_restricted_fixture(string $caseName): bool
{
    return strncmp($caseName, 'private_', strlen('private_')) === 0
        || strncmp($caseName, 'protected_', strlen('protected_')) === 0;
}

function load_public_fixture(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    require write_temp_php_file(<<<'PHP'
<?php
namespace NsVisGate5BenchPublic {
    class PublicFixture {
        public const VALUE = 1;
        public static int $property = 1;
        public int $value = 1;

        public static function method(): int {
            return 1;
        }
    }

    class AutoloadTemplate {}

    function public_new_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $object = new PublicFixture();
            $sum += $object->value;
        }
        return $sum;
    }

    function public_static_method_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += PublicFixture::method();
        }
        return $sum;
    }

    function public_static_property_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += PublicFixture::$property;
        }
        return $sum;
    }

    function public_class_constant_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += PublicFixture::VALUE;
        }
        return $sum;
    }

    function public_instanceof_loop(int $iterations): int {
        $sum = 0;
        $object = new PublicFixture();
        for ($i = 0; $i < $iterations; $i++) {
            $sum += $object instanceof PublicFixture ? 1 : 0;
        }
        return $sum;
    }

    function public_dynamic_lookup_warm_loop(int $iterations): int {
        $sum = 0;
        $class = PublicFixture::class;
        class_exists($class, false);
        for ($i = 0; $i < $iterations; $i++) {
            $sum += class_exists($class, false) ? 1 : 0;
        }
        return $sum;
    }

    function public_callable_validation_loop(int $iterations): int {
        $sum = 0;
        $callable = [PublicFixture::class, 'method'];
        for ($i = 0; $i < $iterations; $i++) {
            $sum += is_callable($callable) ? 1 : 0;
        }
        return $sum;
    }
}
PHP);
    $loaded = true;
}

function load_restricted_fixture(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    require write_temp_php_file(<<<'PHP'
<?php
namespace NsVisGate5BenchRestricted {
    private(namespace) class PrivateFixture {
        public const VALUE = 1;
        public static int $property = 1;
        public int $value = 1;

        public static function method(): int {
            return 1;
        }
    }

    protected(namespace) class ProtectedFixture {
        public const VALUE = 1;
        public static int $property = 1;
        public int $value = 1;

        public static function method(): int {
            return 1;
        }
    }

    function private_allowed_new_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $object = new PrivateFixture();
            $sum += $object->value;
        }
        return $sum;
    }

    function private_allowed_static_method_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += PrivateFixture::method();
        }
        return $sum;
    }

    function private_allowed_static_property_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += PrivateFixture::$property;
        }
        return $sum;
    }

    function private_allowed_class_constant_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += PrivateFixture::VALUE;
        }
        return $sum;
    }

    function private_allowed_instanceof_loop(int $iterations): int {
        $sum = 0;
        $object = new PrivateFixture();
        for ($i = 0; $i < $iterations; $i++) {
            $sum += $object instanceof PrivateFixture ? 1 : 0;
        }
        return $sum;
    }

    function private_allowed_callable_validation_loop(int $iterations): int {
        $sum = 0;
        $callable = [PrivateFixture::class, 'method'];
        for ($i = 0; $i < $iterations; $i++) {
            $sum += is_callable($callable) ? 1 : 0;
        }
        return $sum;
    }
}

namespace NsVisGate5BenchRestricted\Child {
    function protected_allowed_child_new_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $object = new \NsVisGate5BenchRestricted\ProtectedFixture();
            $sum += $object->value;
        }
        return $sum;
    }

    function protected_allowed_child_static_method_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $sum += \NsVisGate5BenchRestricted\ProtectedFixture::method();
        }
        return $sum;
    }

    function protected_allowed_child_instanceof_loop(int $iterations): int {
        $sum = 0;
        $object = new \NsVisGate5BenchRestricted\ProtectedFixture();
        for ($i = 0; $i < $iterations; $i++) {
            $sum += $object instanceof \NsVisGate5BenchRestricted\ProtectedFixture ? 1 : 0;
        }
        return $sum;
    }
}

namespace NsVisGate5BenchRestricted\Consumer {
    function private_denied_new_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            try {
                new \NsVisGate5BenchRestricted\PrivateFixture();
            } catch (\Error $e) {
                $sum++;
            }
        }
        return $sum;
    }

    function private_denied_static_method_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            try {
                \NsVisGate5BenchRestricted\PrivateFixture::method();
            } catch (\Error $e) {
                $sum++;
            }
        }
        return $sum;
    }

    function private_denied_instanceof_loop(int $iterations): int {
        $sum = 0;
        $object = new \stdClass();
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $sum += $object instanceof \NsVisGate5BenchRestricted\PrivateFixture ? 1 : 0;
            } catch (\Error $e) {
                $sum++;
            }
        }
        return $sum;
    }

}

namespace NsVisGate5BenchOutside {
    function protected_denied_new_loop(int $iterations): int {
        $sum = 0;
        for ($i = 0; $i < $iterations; $i++) {
            try {
                new \NsVisGate5BenchRestricted\ProtectedFixture();
            } catch (\Error $e) {
                $sum++;
            }
        }
        return $sum;
    }
}
PHP);
    $loaded = true;
}

function public_autoload_hit_loop(int $iterations, int $runId): int
{
    static $registered = false;
    if (!$registered) {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'NsVisGate5BenchAutoload\\C';
            if (strncmp($class, $prefix, strlen($prefix)) === 0) {
                class_alias(\NsVisGate5BenchPublic\AutoloadTemplate::class, $class);
            }
        });
        $registered = true;
    }

    $sum = 0;
    $base = $runId * $iterations;
    for ($i = 0; $i < $iterations; $i++) {
        $class = 'NsVisGate5BenchAutoload\\C' . (string) ($base + $i);
        $sum += class_exists($class, true) ? 1 : 0;
    }
    return $sum;
}

function supports_namespace_visibility(): bool
{
    $suffix = str_replace('.', '_', uniqid('', true));
    try {
        eval('namespace NsVisGate5Probe' . $suffix . ' { private(namespace) class PrivateProbe {} protected(namespace) class ProtectedProbe {} }');
    } catch (ParseError) {
        return false;
    }
    return true;
}

function probe_namespace_visibility_support(string $php): bool
{
    $process = run_command([$php, __FILE__, '--probe-namespace-visibility'], dirname(__DIR__));
    $decoded = json_decode($process['stdout'], true);
    if (!is_array($decoded) || !array_key_exists('supported', $decoded)) {
        fwrite(STDERR, $process['stdout']);
        fwrite(STDERR, $process['stderr']);
        throw new RuntimeException('Namespace visibility probe did not return JSON');
    }
    return (bool) $decoded['supported'];
}

function generate_memory_fixture(string $kind, int $classes): string
{
    $namespace = match ($kind) {
        'public' => 'NsVisGate5MemoryPublic',
        'private_restricted' => 'NsVisGate5MemoryPrivate',
        'protected_restricted' => 'NsVisGate5MemoryProtected',
        default => throw new InvalidArgumentException('Unknown memory fixture kind: ' . $kind),
    };
    $modifier = match ($kind) {
        'public' => '',
        'private_restricted' => 'private(namespace) ',
        'protected_restricted' => 'protected(namespace) ',
    };

    $code = "<?php\nnamespace " . $namespace . ";\n";
    for ($i = 0; $i < $classes; $i++) {
        $code .= $modifier . 'class C' . $i . " {}\n";
    }
    return $code;
}

function write_temp_php_file(string $code): string
{
    $file = tempnam(sys_get_temp_dir(), 'nsvis_gate5_');
    if ($file === false) {
        throw new RuntimeException('Failed to create temporary file');
    }
    $phpFile = $file . '.php';
    if (!rename($file, $phpFile)) {
        throw new RuntimeException('Failed to create temporary PHP file');
    }
    if (file_put_contents($phpFile, $code) === false) {
        throw new RuntimeException('Failed to write temporary PHP file');
    }
    register_shutdown_function(static function () use ($phpFile): void {
        if (is_file($phpFile)) {
            @unlink($phpFile);
        }
    });
    return $phpFile;
}

function run_command(array $command, ?string $cwd = null): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open(
        implode(' ', array_map('escapeshellarg', $command)),
        $descriptorSpec,
        $pipes,
        $cwd ?? getcwd()
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Failed to start command');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fwrite(STDERR, $stderr);
        throw new RuntimeException('Command failed with exit code ' . $exitCode);
    }

    return ['stdout' => $stdout, 'stderr' => $stderr, 'exit_code' => $exitCode];
}

function parse_options(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (strncmp($arg, '--', 2) !== 0) {
            continue;
        }
        $option = substr($arg, 2);
        $position = strpos($option, '=');
        if ($position === false) {
            $options[$option] = true;
        } else {
            $options[substr($option, 0, $position)] = substr($option, $position + 1);
        }
    }
    return $options;
}

function require_option(array $options, string $name): string
{
    if (!isset($options[$name]) || !is_string($options[$name])) {
        throw new InvalidArgumentException('Missing required option --' . $name);
    }
    return $options[$name];
}

function env_int(string $name, int $default): int
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }
    return positive_int($value, $name);
}

function env_non_negative_int(string $name, int $default): int
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }
    return non_negative_int($value, $name);
}

function positive_int(string $value, string $name): int
{
    $int = parse_decimal_int($value);
    if ($int === null || $int < 1) {
        throw new InvalidArgumentException($name . ' must be a positive integer');
    }
    return $int;
}

function non_negative_int(string $value, string $name): int
{
    $int = parse_decimal_int($value);
    if ($int === null) {
        throw new InvalidArgumentException($name . ' must be a non-negative integer');
    }
    return $int;
}

function parse_decimal_int(string $value): ?int
{
    if ($value === '') {
        return null;
    }

    $result = 0;
    $length = strlen($value);
    for ($i = 0; $i < $length; $i++) {
        $digit = ord($value[$i]) - ord('0');
        if ($digit < 0 || $digit > 9) {
            return null;
        }
        $result = $result * 10 + $digit;
    }
    return $result;
}

function ini_bool(string $name): bool
{
    $value = strtolower((string) ini_get($name));
    return $value === '1' || $value === 'on' || $value === 'true' || $value === 'yes';
}

function median(array $values): float
{
    sort($values, SORT_NUMERIC);
    $count = count($values);
    $middle = intdiv($count, 2);
    if ($count % 2 === 1) {
        return (float) $values[$middle];
    }
    return ($values[$middle - 1] + $values[$middle]) / 2;
}

function stddev(array $values): float
{
    $count = count($values);
    if ($count === 0) {
        return 0.0;
    }
    $mean = array_sum($values) / $count;
    $sum = 0.0;
    foreach ($values as $value) {
        $sum += ($value - $mean) * ($value - $mean);
    }
    return sqrt($sum / $count);
}

function relative_stddev(array $values): float
{
    $mean = array_sum($values) / count($values);
    if ($mean == 0.0) {
        return 0.0;
    }
    return stddev($values) / $mean;
}
