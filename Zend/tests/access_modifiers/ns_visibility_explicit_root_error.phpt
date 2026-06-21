--TEST--
Explicit namespace visibility root is rejected in the Phase B spike
--FILE--
<?php

protected(namespace: \Acme\Billing) class Invalid {}

?>
--EXPECTF--
Parse error: syntax error, unexpected token "protected", expecting end of file in %s on line %d
