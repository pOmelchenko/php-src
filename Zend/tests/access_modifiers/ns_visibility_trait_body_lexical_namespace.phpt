--TEST--
Namespace visibility uses trait body lexical namespace after composition
--FILE--
<?php

namespace Gate3\TraitBody\Library {
    private(namespace) class Secret {}

    trait AllowedTrait {
        public function make(): object {
            return new Secret();
        }
    }

    class UsesExternalTrait {
        use \Gate3\TraitBody\App\DeniedTrait {
            make as makeAlias;
            make as private privateMake;
        }

        public function callPrivateAlias(): object {
            return $this->privateMake();
        }
    }

    class UsesPrecedence {
        use AllowedTrait, \Gate3\TraitBody\App\DeniedTrait {
            AllowedTrait::make insteadof \Gate3\TraitBody\App\DeniedTrait;
            \Gate3\TraitBody\App\DeniedTrait::make as deniedMake;
        }
    }
}

namespace Gate3\TraitBody\App {
    trait DeniedTrait {
        public function make(): object {
            return new \Gate3\TraitBody\Library\Secret();
        }
    }

    class UsesLibraryTrait {
        use \Gate3\TraitBody\Library\AllowedTrait {
            make as makeAlias;
            make as private privateMake;
        }

        public function callPrivateAlias(): object {
            return $this->privateMake();
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
    report('allowed-private-alias-outside', fn() => $outside->callPrivateAlias());
}

namespace Gate3\TraitBody\Library {
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
    report('denied-private-alias-inside', fn() => $inside->callPrivateAlias());

    $precedence = new UsesPrecedence();
    report('precedence-keeps-selected-trait-namespace', fn() => $precedence->make());
    report('precedence-alias-keeps-original-trait-namespace', fn() => $precedence->deniedMake());
}

?>
--EXPECT--
allowed-method-outside: Gate3\TraitBody\Library\Secret
allowed-alias-outside: Gate3\TraitBody\Library\Secret
allowed-private-alias-outside: Gate3\TraitBody\Library\Secret
denied-method-inside: Error: Cannot access private(namespace) class Gate3\TraitBody\Library\Secret from namespace Gate3\TraitBody\App
denied-alias-inside: Error: Cannot access private(namespace) class Gate3\TraitBody\Library\Secret from namespace Gate3\TraitBody\App
denied-private-alias-inside: Error: Cannot access private(namespace) class Gate3\TraitBody\Library\Secret from namespace Gate3\TraitBody\App
precedence-keeps-selected-trait-namespace: Gate3\TraitBody\Library\Secret
precedence-alias-keeps-original-trait-namespace: Error: Cannot access private(namespace) class Gate3\TraitBody\Library\Secret from namespace Gate3\TraitBody\App
