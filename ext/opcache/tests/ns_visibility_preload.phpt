--TEST--
Namespace visibility metadata and checks survive preloading
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.preload={PWD}/ns_visibility_preload.inc
opcache.jit=0
--EXTENSIONS--
opcache
--SKIPIF--
<?php
if (PHP_OS_FAMILY == 'Windows') die('skip Preloading is not supported on Windows');
?>
--FILE--
<?php

namespace Gate4\Preload\Library {
    echo 'same-class: ', Hidden::label(), "\n";
    echo 'same-property: ', Hidden::$property, "\n";
    echo 'same-constant: ', Hidden::VALUE, "\n";
    echo 'same-new: ', (new Hidden() instanceof Hidden ? 'true' : 'false'), "\n";
    echo 'same-enum: ', HiddenEnum::CaseA->name, "\n";

    class RequestTraitUser {
        use HiddenTrait;
    }

    echo 'same-trait: ', (new RequestTraitUser())->traitLabel(), "\n";
}

namespace Gate4\Preload\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_object($value)) {
                $value = get_class($value);
            }
            echo $label, ': ', $value === null ? 'ok' : $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    $object = \Gate4\Preload\Library\PublicFactory::make();
    report('factory-object-class', fn() => get_class($object));
    report('factory-object-method', fn() => $object->instanceMethod());
    report('factory-object-clone', fn() => get_class(clone $object));

    foreach ([
        'class' => \Gate4\Preload\Library\Hidden::class,
        'interface' => \Gate4\Preload\Library\HiddenInterface::class,
        'trait' => \Gate4\Preload\Library\HiddenTrait::class,
        'enum' => \Gate4\Preload\Library\HiddenEnum::class,
    ] as $label => $name) {
        $reflection = new \ReflectionClass($name);
        echo 'metadata-', $label, ': private=',
            $reflection->isNamespacePrivate() ? 'true' : 'false',
            ' protected=', $reflection->isNamespaceProtected() ? 'true' : 'false',
            ' root=', $reflection->getNamespaceVisibilityRoot(), "\n";
    }

    report('denied-new', fn() => new \Gate4\Preload\Library\Hidden());
    report('denied-static', fn() => \Gate4\Preload\Library\Hidden::label());
    report('denied-enum', fn() => \Gate4\Preload\Library\HiddenEnum::CaseA);
    report('denied-instanceof', fn() => $object instanceof \Gate4\Preload\Library\Hidden);
    report('reflection-new-instance', function () {
        $reflection = new \ReflectionClass(\Gate4\Preload\Library\Hidden::class);
        return $reflection->newInstanceWithoutConstructor();
    });
}

?>
--EXPECT--
same-class: hidden
same-property: property
same-constant: value
same-new: true
same-enum: CaseA
same-trait: trait
factory-object-class: Gate4\Preload\Library\Hidden
factory-object-method: instance
factory-object-clone: Gate4\Preload\Library\Hidden
metadata-class: private=true protected=false root=gate4\preload\library
metadata-interface: private=true protected=false root=gate4\preload\library
metadata-trait: private=true protected=false root=gate4\preload\library
metadata-enum: private=true protected=false root=gate4\preload\library
denied-new: Error: Cannot access private(namespace) class Gate4\Preload\Library\Hidden from namespace Gate4\Preload\Consumer
denied-static: Error: Cannot access private(namespace) class Gate4\Preload\Library\Hidden from namespace Gate4\Preload\Consumer
denied-enum: Error: Cannot access private(namespace) enum Gate4\Preload\Library\HiddenEnum from namespace Gate4\Preload\Consumer
denied-instanceof: Error: Cannot access private(namespace) class Gate4\Preload\Library\Hidden from namespace Gate4\Preload\Consumer
reflection-new-instance: Error: Cannot access private(namespace) class Gate4\Preload\Library\Hidden from namespace Gate4\Preload\Consumer
