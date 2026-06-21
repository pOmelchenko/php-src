--TEST--
Namespace visibility JIT respects top-level namespace ranges
--EXTENSIONS--
opcache
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=1255
opcache.jit_buffer_size=64M
opcache.jit_hot_func=1
opcache.jit_hot_loop=1
opcache.jit_hot_return=1
opcache.jit_hot_side_exit=1
--SKIPIF--
<?php
if (ini_get('opcache.jit') === false) die('skip PHP is compiled without JIT');
?>
--FILE--
<?php

namespace GateJitRanges\MiXeDCase {
    private(namespace) class Hidden {
        public static string $property = 'property';

        public static function label(): string {
            return 'hidden';
        }
    }

    for ($i = 0; $i < 16; $i++) {
        Hidden::label();
        Hidden::$property;
    }
    echo 'library-1: ', Hidden::label(), '/', Hidden::$property, "\n";
}

namespace GateJitRanges\OtherCase {
    for ($i = 0; $i < 16; $i++) {
        try {
            \GateJitRanges\MiXeDCase\Hidden::label();
        } catch (\Throwable) {
        }
        try {
            \GateJitRanges\MiXeDCase\Hidden::$property;
        } catch (\Throwable) {
        }
    }

    try {
        \GateJitRanges\MiXeDCase\Hidden::label();
    } catch (\Throwable $e) {
        echo 'consumer-method: ', get_class($e), ': ', $e->getMessage(), "\n";
    }

    try {
        \GateJitRanges\MiXeDCase\Hidden::$property;
    } catch (\Throwable $e) {
        echo 'consumer-property: ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}

namespace GateJitRanges\MiXeDCase {
    for ($i = 0; $i < 16; $i++) {
        Hidden::label();
    }
    echo 'library-2: ', Hidden::label(), "\n";
}

?>
--EXPECT--
library-1: hidden/property
consumer-method: Error: Cannot access private(namespace) class GateJitRanges\MiXeDCase\Hidden from namespace GateJitRanges\OtherCase
consumer-property: Error: Cannot access private(namespace) class GateJitRanges\MiXeDCase\Hidden from namespace GateJitRanges\OtherCase
library-2: hidden
