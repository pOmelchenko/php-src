--TEST--
Namespace visibility metadata survives OPcache file cache replay
--EXTENSIONS--
opcache
--FILE--
<?php

function removeDirRecursive(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isDir()) {
            @rmdir($fileinfo->getPathname());
        } else {
            @unlink($fileinfo->getPathname());
        }
    }
    @rmdir($dir);
}

$cacheDir = __DIR__ . '/ns_visibility_opcache_file_cache.cache';
removeDirRecursive($cacheDir);
mkdir($cacheDir);

$php = getenv('TEST_PHP_EXECUTABLE_ESCAPED');
$args = trim((string) getenv('TEST_PHP_EXTRA_ARGS'));
$fixture = __DIR__ . '/ns_visibility_opcache_file_cache.inc';
$cmd = $php
    . ($args !== '' ? ' ' . $args : '')
    . ' -d opcache.enable=1'
    . ' -d opcache.enable_cli=1'
    . ' -d opcache.optimization_level=-1'
    . ' -d opcache.jit=0'
    . ' -d opcache.file_cache=' . escapeshellarg($cacheDir)
    . ' -d opcache.file_cache_only=1'
    . ' -d opcache.file_update_protection=0'
    . ' -d opcache.validate_timestamps=0'
    . ' ' . escapeshellarg($fixture);

for ($i = 1; $i <= 2; $i++) {
    echo "run $i\n";
    passthru($cmd, $status);
    if ($status !== 0) {
        echo "exit status: $status\n";
    }
}

?>
--CLEAN--
<?php
function removeDirRecursive(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isDir()) {
            @rmdir($fileinfo->getPathname());
        } else {
            @unlink($fileinfo->getPathname());
        }
    }
    @rmdir($dir);
}
removeDirRecursive(__DIR__ . '/ns_visibility_opcache_file_cache.cache');
?>
--EXPECT--
run 1
metadata: private=true protected=false root=gate4\filecache\library
library-1: hidden
consumer-new: Error: Cannot access private(namespace) class Gate4\FileCache\Library\Hidden from namespace Gate4\FileCache\Consumer
consumer-static: Error: Cannot access private(namespace) class Gate4\FileCache\Library\Hidden from namespace Gate4\FileCache\Consumer
consumer-instanceof: Error: Cannot access private(namespace) class Gate4\FileCache\Library\Hidden from namespace Gate4\FileCache\Consumer
consumer-object-class: Gate4\FileCache\Library\Hidden
consumer-object-method: instance
library-2: hidden
run 2
metadata: private=true protected=false root=gate4\filecache\library
library-1: hidden
consumer-new: Error: Cannot access private(namespace) class Gate4\FileCache\Library\Hidden from namespace Gate4\FileCache\Consumer
consumer-static: Error: Cannot access private(namespace) class Gate4\FileCache\Library\Hidden from namespace Gate4\FileCache\Consumer
consumer-instanceof: Error: Cannot access private(namespace) class Gate4\FileCache\Library\Hidden from namespace Gate4\FileCache\Consumer
consumer-object-class: Gate4\FileCache\Library\Hidden
consumer-object-method: instance
library-2: hidden
