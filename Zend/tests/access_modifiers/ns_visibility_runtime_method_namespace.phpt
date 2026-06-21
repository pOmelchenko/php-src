--TEST--
Namespace visibility runtime checks use method lexical namespace
--FILE--
<?php

namespace Acme\Billing {
    private(namespace) class MethodPrivateService {
        public function label(): string { return 'method-private'; }
    }

    class SameNamespaceFactory {
        public function create(): object {
            return new MethodPrivateService();
        }
    }
}

namespace Acme\Billing\Application {
    class ChildNamespaceFactory {
        public function create(): object {
            return new \Acme\Billing\MethodPrivateService();
        }
    }
}

namespace {
    echo (new \Acme\Billing\SameNamespaceFactory())->create()->label(), "\n";

    try {
        (new \Acme\Billing\Application\ChildNamespaceFactory())->create();
    } catch (Error $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}

?>
--EXPECT--
method-private
Error: Cannot access private(namespace) class Acme\Billing\MethodPrivateService from namespace acme\billing\application
