--TEST--
Namespace visibility OPcache preserves top-level namespace ranges
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=0
--EXTENSIONS--
opcache
--FILE--
<?php

namespace Gate4\OpcacheRanges\MiXeDCase {
    private(namespace) class Hidden {
        public static function label(): string {
            return 'hidden';
        }
    }

    echo 'library-1: ', Hidden::label(), "\n";
}

namespace Gate4\OpcacheRanges\OtherCase {
    try {
        \Gate4\OpcacheRanges\MiXeDCase\Hidden::label();
    } catch (\Throwable $e) {
        echo 'consumer: ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}

namespace Gate4\OpcacheRanges\MiXeDCase {
    echo 'library-2: ', Hidden::label(), "\n";
}

?>
--EXPECT--
library-1: hidden
consumer: Error: Cannot access private(namespace) class Gate4\OpcacheRanges\MiXeDCase\Hidden from namespace Gate4\OpcacheRanges\OtherCase
library-2: hidden
