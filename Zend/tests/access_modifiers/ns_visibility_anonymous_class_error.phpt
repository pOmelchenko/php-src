--TEST--
Namespace visibility modifier is rejected on anonymous classes
--FILE--
<?php

new private(namespace) class {};

?>
--EXPECTF--
Parse error: syntax error, unexpected token "private(namespace)", expecting "class" in %s on line %d
