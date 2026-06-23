--TEST--
Namespace visibility for ReflectionAttribute::newInstance() survives preloading
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.preload={PWD}/ns_visibility_attribute_new_instance_preload.inc
opcache.jit=0
--EXTENSIONS--
opcache
--SKIPIF--
<?php
if (PHP_OS_FAMILY == 'Windows') die('skip Preloading is not supported on Windows');
?>
--FILE--
<?php

namespace Gate4\AttributePreload\Consumer {
    function report(string $label, \ReflectionAttribute $attribute): void {
        try {
            $object = $attribute->newInstance();
            echo $label, ': ', $object->label, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('same-class',
        (new \ReflectionClass(\Gate4\AttributePreload\Lib\SameTarget::class))->getAttributes()[0]);
    report('denied-class',
        (new \ReflectionClass(DeniedTarget::class))->getAttributes()[0]);
    report('protected-child',
        (new \ReflectionClass(\Gate4\AttributePreload\Lib\Child\ChildTarget::class))->getAttributes()[0]);
    report('protected-sibling',
        (new \ReflectionClass(\Gate4\AttributePreload\Sibling\SiblingTarget::class))->getAttributes()[0]);
}

?>
--EXPECT--
same-class: same-class
denied-class: Error: Cannot access private(namespace) class Gate4\AttributePreload\Lib\HiddenAttribute from namespace Gate4\AttributePreload\Consumer
protected-child: child-class
protected-sibling: Error: Cannot access protected(namespace) class Gate4\AttributePreload\Lib\ProtectedAttribute from namespace Gate4\AttributePreload\Sibling
