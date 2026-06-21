--TEST--
Namespace visibility runtime checks for dynamic new
--FILE--
<?php

namespace Acme\Billing {
    protected(namespace) class DynamicService {
        public function label(): string { return 'dynamic'; }
    }

    function newDynamicSame(): object {
        $class = DynamicService::class;
        return new $class();
    }
}

namespace Acme\Billing\Application {
    function newDynamicChild(): object {
        $class = \Acme\Billing\DynamicService::class;
        return new $class();
    }
}

namespace Acme\Other {
    function newDynamicSibling(): object {
        $class = \Acme\Billing\DynamicService::class;
        return new $class();
    }
}

namespace {
    echo \Acme\Billing\newDynamicSame()->label(), "\n";
    echo \Acme\Billing\Application\newDynamicChild()->label(), "\n";

    foreach ([
        \Acme\Other\newDynamicSibling(...),
    ] as $callback) {
        try {
            $callback();
        } catch (Error $e) {
            echo get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
}

?>
--EXPECT--
dynamic
dynamic
Error: Cannot access protected(namespace) class Acme\Billing\DynamicService from namespace acme\other
