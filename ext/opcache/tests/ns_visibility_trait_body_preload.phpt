--TEST--
Namespace visibility trait body lexical namespace survives preloading
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.preload={PWD}/ns_visibility_trait_body_preload.inc
opcache.jit=0
--EXTENSIONS--
opcache
--SKIPIF--
<?php
if (PHP_OS_FAMILY == 'Windows') die('skip Preloading is not supported on Windows');
?>
--FILE--
<?php

namespace Gate4\TraitBodyPreload\App {
    class UsesLibraryTrait {
        use \Gate4\TraitBodyPreload\Library\AllowedTrait {
            make as makeAlias;
        }
    }

    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            if (is_object($value)) {
                $value = get_class($value);
            }
            echo $label, ': ', $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    $outside = new UsesLibraryTrait();
    report('allowed-method-outside', fn() => $outside->make());
    report('allowed-alias-outside', fn() => $outside->makeAlias());
}

namespace Gate4\TraitBodyPreload\Library {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            if (is_object($value)) {
                $value = get_class($value);
            }
            echo $label, ': ', $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    $inside = new UsesExternalTrait();
    report('denied-method-inside', fn() => $inside->make());
}

?>
--EXPECT--
allowed-method-outside: Gate4\TraitBodyPreload\Library\Secret
allowed-alias-outside: Gate4\TraitBodyPreload\Library\Secret
denied-method-inside: Error: Cannot access private(namespace) class Gate4\TraitBodyPreload\Library\Secret from namespace Gate4\TraitBodyPreload\App
