--TEST--
Namespace visibility syntax on class-like declarations
--FILE--
<?php

namespace Acme\Billing;

private(namespace) final class PrivateFinalClass {}
protected(namespace) abstract class ProtectedAbstractClass {}
private(namespace) readonly class PrivateReadonlyClass {}
private(namespace) interface PrivateInterface {}
protected(namespace) trait ProtectedTrait {}
private(namespace) enum PrivateEnum { case A; }

echo "ok\n";

?>
--EXPECT--
ok
