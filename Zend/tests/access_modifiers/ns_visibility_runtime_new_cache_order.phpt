--TEST--
Namespace visibility checks are not skipped by class-entry caches
--FILE--
<?php

namespace Acme\Billing {
    protected(namespace) class CachedService {}

    function newCachedAllowed(): object {
        return new CachedService();
    }
}

namespace Acme\Other {
    function newCachedDenied(): object {
        return new \Acme\Billing\CachedService();
    }
}

namespace {
    echo "allowed-then-denied\n";
    \Acme\Billing\newCachedAllowed();
    try {
        \Acme\Other\newCachedDenied();
    } catch (Error $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }

    echo "denied-then-allowed\n";
    try {
        \Acme\Other\newCachedDenied();
    } catch (Error $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
    \Acme\Billing\newCachedAllowed();
    echo "allowed after denied\n";
}

?>
--EXPECT--
allowed-then-denied
Error: Cannot access protected(namespace) class Acme\Billing\CachedService from namespace Acme\Other
denied-then-allowed
Error: Cannot access protected(namespace) class Acme\Billing\CachedService from namespace Acme\Other
allowed after denied
