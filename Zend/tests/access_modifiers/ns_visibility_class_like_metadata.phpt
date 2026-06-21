--TEST--
Namespace visibility metadata on class-like declarations
--FILE--
<?php

namespace Acme\Billing {
    class PublicClass {}
    private(namespace) class PrivateClass {}
    protected(namespace) class ProtectedClass {}
    private(namespace) interface PrivateInterface {}
    protected(namespace) trait ProtectedTrait {}
    private(namespace) enum PrivateEnum { case A; }
}

namespace {
    private(namespace) class GlobalPrivateClass {}

    foreach ([
        GlobalPrivateClass::class,
        Acme\Billing\PublicClass::class,
        Acme\Billing\PrivateClass::class,
        Acme\Billing\ProtectedClass::class,
        Acme\Billing\PrivateInterface::class,
        Acme\Billing\ProtectedTrait::class,
        Acme\Billing\PrivateEnum::class,
    ] as $class) {
        $reflection = new ReflectionClass($class);
        echo $reflection->getName(), "\n";
        var_dump($reflection->isNamespacePrivate());
        var_dump($reflection->isNamespaceProtected());
        var_dump($reflection->getNamespaceVisibilityRoot());
    }
}

?>
--EXPECT--
GlobalPrivateClass
bool(true)
bool(false)
string(0) ""
Acme\Billing\PublicClass
bool(false)
bool(false)
NULL
Acme\Billing\PrivateClass
bool(true)
bool(false)
string(12) "Acme\Billing"
Acme\Billing\ProtectedClass
bool(false)
bool(true)
string(12) "Acme\Billing"
Acme\Billing\PrivateInterface
bool(true)
bool(false)
string(12) "Acme\Billing"
Acme\Billing\ProtectedTrait
bool(false)
bool(true)
string(12) "Acme\Billing"
Acme\Billing\PrivateEnum
bool(true)
bool(false)
string(12) "Acme\Billing"
