--TEST--
Namespace visibility Gate 3 lexical caller contexts
--FILE--
<?php

namespace Gate3\Lexical\Library {
    private(namespace) class Hidden {}

    function from_function(): object {
        return new Hidden();
    }

    class Host {
        public function from_method(): object {
            return new Hidden();
        }
    }

    $GLOBALS['gate3_allowed_closure'] = function (): object {
        return new Hidden();
    };
    $GLOBALS['gate3_allowed_arrow'] = fn(): object => new Hidden();
}

namespace Gate3\Lexical\Consumer {
    class Foreign {}

    $GLOBALS['gate3_denied_closure'] = function (): object {
        return new \Gate3\Lexical\Library\Hidden();
    };
}

namespace Gate3\Lexical\Library {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            echo $label, ': ', get_class($value), "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('function', from_function(...));
    report('method', fn() => (new Host())->from_method());
    report('closure', $GLOBALS['gate3_allowed_closure']);
    report('arrow', $GLOBALS['gate3_allowed_arrow']);
    report('bind-keeps-declaration-namespace', fn() =>
        $GLOBALS['gate3_allowed_closure']->bindTo(
            new \Gate3\Lexical\Consumer\Foreign(),
            \Gate3\Lexical\Consumer\Foreign::class
        )()
    );
    report('call-keeps-declaration-namespace', fn() =>
        $GLOBALS['gate3_allowed_closure']->call(new \Gate3\Lexical\Consumer\Foreign())
    );
}

namespace Gate3\Lexical\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            echo $label, ': ', get_class($value), "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('bind-does-not-grant-library-namespace', fn() =>
        $GLOBALS['gate3_denied_closure']->bindTo(
            new \Gate3\Lexical\Library\Host(),
            \Gate3\Lexical\Library\Host::class
        )()
    );
}

?>
--EXPECT--
function: Gate3\Lexical\Library\Hidden
method: Gate3\Lexical\Library\Hidden
closure: Gate3\Lexical\Library\Hidden
arrow: Gate3\Lexical\Library\Hidden
bind-keeps-declaration-namespace: Gate3\Lexical\Library\Hidden
call-keeps-declaration-namespace: Gate3\Lexical\Library\Hidden
bind-does-not-grant-library-namespace: Error: Cannot access private(namespace) class Gate3\Lexical\Library\Hidden from namespace Gate3\Lexical\Consumer
