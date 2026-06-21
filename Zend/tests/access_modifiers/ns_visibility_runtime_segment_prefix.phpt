--TEST--
Namespace visibility rejects segment-prefix false positives
--FILE--
<?php

namespace Acme\Billing {
    protected(namespace) class SegmentService {}
}

namespace Acme\BillingExtra {
    function newFromPrefixNamespace(): object {
        return new \Acme\Billing\SegmentService();
    }
}

namespace {
    try {
        \Acme\BillingExtra\newFromPrefixNamespace();
    } catch (Error $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}

?>
--EXPECT--
Error: Cannot access protected(namespace) class Acme\Billing\SegmentService from namespace Acme\BillingExtra
