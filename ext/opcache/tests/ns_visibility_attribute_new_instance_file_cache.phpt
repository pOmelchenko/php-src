--TEST--
Namespace visibility for ReflectionAttribute::newInstance() survives OPcache file cache replay
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

$cacheDir = __DIR__ . '/ns_visibility_attribute_new_instance_file_cache.cache';
removeDirRecursive($cacheDir);
mkdir($cacheDir);

$php = getenv('TEST_PHP_EXECUTABLE_ESCAPED');
$args = trim((string) getenv('TEST_PHP_EXTRA_ARGS'));
$fixture = __DIR__ . '/ns_visibility_attribute_new_instance.inc';
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
removeDirRecursive(__DIR__ . '/ns_visibility_attribute_new_instance_file_cache.cache');
?>
--EXPECT--
run 1
same-class: same-class
denied-class: Error: Cannot access private(namespace) class Gate4\AttributeOpcache\Lib\HiddenAttribute from namespace Gate4\AttributeOpcache\Consumer
protected-child: child-class
protected-sibling: Error: Cannot access protected(namespace) class Gate4\AttributeOpcache\Lib\ProtectedAttribute from namespace Gate4\AttributeOpcache\Sibling
run 2
same-class: same-class
denied-class: Error: Cannot access private(namespace) class Gate4\AttributeOpcache\Lib\HiddenAttribute from namespace Gate4\AttributeOpcache\Consumer
protected-child: child-class
protected-sibling: Error: Cannot access protected(namespace) class Gate4\AttributeOpcache\Lib\ProtectedAttribute from namespace Gate4\AttributeOpcache\Sibling
