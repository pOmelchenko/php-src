--TEST--
Duplicate namespace visibility modifiers are rejected
--FILE--
<?php

private(namespace) protected(namespace) class Invalid {}

?>
--EXPECTF--
Parse error: syntax error, unexpected token "protected(namespace)", expecting "class" in %s on line %d
