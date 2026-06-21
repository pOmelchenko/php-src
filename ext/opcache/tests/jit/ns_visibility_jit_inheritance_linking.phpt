--TEST--
Namespace visibility inheritance linking is unchanged with JIT enabled
--EXTENSIONS--
opcache
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=1255
opcache.jit_buffer_size=64M
opcache.jit_hot_func=1
opcache.jit_hot_loop=1
opcache.jit_hot_return=1
opcache.jit_hot_side_exit=1
--SKIPIF--
<?php
if (ini_get('opcache.jit') === false) die('skip PHP is compiled without JIT');
?>
--FILE--
<?php

namespace GateJitLink\Library {
    private(namespace) class ParentClass {
        protected static function label(): string {
            return 'parent';
        }
    }

    private(namespace) interface Contract {
        public function contract(): string;
    }

    private(namespace) trait HelperTrait {
        public function helper(): string {
            return 'helper';
        }
    }

    class Child extends ParentClass {
        public static function parentLabel(): string {
            return parent::label();
        }
    }

    class Implementer implements Contract {
        public function contract(): string {
            return 'contract';
        }
    }

    class TraitUser {
        use HelperTrait;
    }

    enum ContractEnum implements Contract {
        case One;

        public function contract(): string {
            return 'enum';
        }
    }
}

namespace GateJitLink\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            echo $label, ': ', $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    for ($i = 0; $i < 16; $i++) {
        \GateJitLink\Library\Child::parentLabel();
        (new \GateJitLink\Library\Implementer())->contract();
        (new \GateJitLink\Library\TraitUser())->helper();
        \GateJitLink\Library\ContractEnum::One->contract();
    }

    report('allowed-parent', fn() => \GateJitLink\Library\Child::parentLabel());
    report('allowed-implements', fn() => (new \GateJitLink\Library\Implementer())->contract());
    report('allowed-trait', fn() => (new \GateJitLink\Library\TraitUser())->helper());
    report('allowed-enum', fn() => \GateJitLink\Library\ContractEnum::One->contract());

    report('bad-parent', fn() =>
        eval('namespace GateJitLink\Consumer; class BadParent extends \GateJitLink\Library\ParentClass {}')
    );
    report('bad-implements', fn() =>
        eval('namespace GateJitLink\Consumer; class BadImplementer implements \GateJitLink\Library\Contract { public function contract(): string { return "bad"; } }')
    );
    report('bad-trait-use', fn() =>
        eval('namespace GateJitLink\Consumer; class BadTraitUse { use \GateJitLink\Library\HelperTrait; }')
    );
    report('bad-enum-implements', fn() =>
        eval('namespace GateJitLink\Consumer; enum BadEnum implements \GateJitLink\Library\Contract { case One; public function contract(): string { return "bad"; } }')
    );

    foreach ([
        'bad-parent-usable' => BadParent::class,
        'bad-implements-usable' => BadImplementer::class,
        'bad-trait-use-usable' => BadTraitUse::class,
        'bad-enum-usable' => BadEnum::class,
    ] as $label => $class) {
        $usable = class_exists($class, false) || interface_exists($class, false) || enum_exists($class, false);
        echo $label, ': ', $usable ? 'yes' : 'no', "\n";
    }
}

?>
--EXPECT--
allowed-parent: parent
allowed-implements: contract
allowed-trait: helper
allowed-enum: enum
bad-parent: Error: Cannot access private(namespace) class GateJitLink\Library\ParentClass from namespace GateJitLink\Consumer
bad-implements: Error: Cannot access private(namespace) interface GateJitLink\Library\Contract from namespace GateJitLink\Consumer
bad-trait-use: Error: Cannot access private(namespace) trait GateJitLink\Library\HelperTrait from namespace GateJitLink\Consumer
bad-enum-implements: Error: Cannot access private(namespace) interface GateJitLink\Library\Contract from namespace GateJitLink\Consumer
bad-parent-usable: no
bad-implements-usable: no
bad-trait-use-usable: no
bad-enum-usable: no
