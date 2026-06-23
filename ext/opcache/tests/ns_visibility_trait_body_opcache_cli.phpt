--TEST--
Namespace visibility trait body lexical namespace survives OPcache CLI caches
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=0
--EXTENSIONS--
opcache
--FILE--
<?php

namespace Gate4\TraitBodyCli\Library {
    private(namespace) class Secret {}

    trait AllowedTrait {
        public function make(): object {
            return new Secret();
        }
    }

    class UsesExternalTrait {
        use \Gate4\TraitBodyCli\App\DeniedTrait {
            make as makeAlias;
        }
    }
}

namespace Gate4\TraitBodyCli\App {
    trait DeniedTrait {
        public function make(): object {
            return new \Gate4\TraitBodyCli\Library\Secret();
        }
    }

    class UsesLibraryTrait {
        use \Gate4\TraitBodyCli\Library\AllowedTrait {
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

namespace Gate4\TraitBodyCli\Library {
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
    report('denied-alias-inside', fn() => $inside->makeAlias());
}

?>
--EXPECT--
allowed-method-outside: Gate4\TraitBodyCli\Library\Secret
allowed-alias-outside: Gate4\TraitBodyCli\Library\Secret
denied-method-inside: Error: Cannot access private(namespace) class Gate4\TraitBodyCli\Library\Secret from namespace Gate4\TraitBodyCli\App
denied-alias-inside: Error: Cannot access private(namespace) class Gate4\TraitBodyCli\Library\Secret from namespace Gate4\TraitBodyCli\App
