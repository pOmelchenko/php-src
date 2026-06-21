--TEST--
Namespace visibility Gate 3 top-level namespace ranges
--FILE--
<?php

namespace Gate3\Ranges\Library {
    private(namespace) class Hidden {
        public static function label(): string {
            return 'hidden';
        }
    }

    echo 'library-1: ', Hidden::label(), "\n";
}

namespace Gate3\Ranges\Consumer {
    try {
        \Gate3\Ranges\Library\Hidden::label();
    } catch (\Throwable $e) {
        echo 'consumer: ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}

namespace Gate3\Ranges\Library {
    echo 'library-2: ', Hidden::label(), "\n";
}

?>
--EXPECT--
library-1: hidden
consumer: Error: Cannot access private(namespace) class Gate3\Ranges\Library\Hidden from namespace Gate3\Ranges\Consumer
library-2: hidden
