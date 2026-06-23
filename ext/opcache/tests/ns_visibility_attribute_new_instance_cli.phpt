--TEST--
Namespace visibility for ReflectionAttribute::newInstance() survives OPcache CLI caching
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=0
--EXTENSIONS--
opcache
--FILE--
<?php
require __DIR__ . '/ns_visibility_attribute_new_instance.inc';
?>
--EXPECT--
same-class: same-class
denied-class: Error: Cannot access private(namespace) class Gate4\AttributeOpcache\Lib\HiddenAttribute from namespace Gate4\AttributeOpcache\Consumer
protected-child: child-class
protected-sibling: Error: Cannot access protected(namespace) class Gate4\AttributeOpcache\Lib\ProtectedAttribute from namespace Gate4\AttributeOpcache\Sibling
