<?php

namespace Gate4\TraitBodyFileCache\Library {
    private(namespace) class Secret {}

    trait AllowedTrait {
        public function make(): object {
            return new Secret();
        }
    }

    class UsesExternalTrait {
        use \Gate4\TraitBodyFileCache\App\DeniedTrait {
            make as makeAlias;
        }
    }
}

namespace Gate4\TraitBodyFileCache\App {
    trait DeniedTrait {
        public function make(): object {
            return new \Gate4\TraitBodyFileCache\Library\Secret();
        }
    }

    class UsesLibraryTrait {
        use \Gate4\TraitBodyFileCache\Library\AllowedTrait {
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

namespace Gate4\TraitBodyFileCache\Library {
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
