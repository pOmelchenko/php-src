--TEST--
Namespace visibility Gate 3 direct require does not grant use access
--FILE--
<?php

namespace {
    $file = __DIR__ . '/ns_visibility_gate3_required.inc';
    file_put_contents($file, <<<'PHP'
<?php
namespace Gate3\RequireLibrary;

private(namespace) class Hidden {
    public static function label(): string {
        return 'hidden';
    }
}
PHP);
    require $file;
}

namespace Gate3\RequireConsumer {
    try {
        \Gate3\RequireLibrary\Hidden::label();
    } catch (\Throwable $e) {
        echo 'consumer: ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}

namespace Gate3\RequireLibrary {
    echo 'library: ', Hidden::label(), "\n";
}

?>
--CLEAN--
<?php
@unlink(__DIR__ . '/ns_visibility_gate3_required.inc');
?>
--EXPECT--
consumer: Error: Cannot access private(namespace) class Gate3\RequireLibrary\Hidden from namespace Gate3\RequireConsumer
library: hidden
