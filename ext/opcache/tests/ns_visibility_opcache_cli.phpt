--TEST--
Namespace visibility is enforced with OPcache CLI caches
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=0
--EXTENSIONS--
opcache
--FILE--
<?php

namespace Gate4\OpcacheCli\Library {
    private(namespace) class Hidden {
        public static string $property = 'property';
        public const VALUE = 'constant';

        public static function method(): string {
            return 'method';
        }
    }

    private(namespace) class SecondHidden {}

    function exerciseAllowed(): void {
        echo 'allowed-new: ', get_class(new Hidden()), "\n";
        echo 'allowed-static-method: ', Hidden::method(), "\n";
        echo 'allowed-static-property: ', Hidden::$property, "\n";
        Hidden::$property = 'changed';
        echo 'allowed-static-property-write: ', Hidden::$property, "\n";
        echo 'allowed-constant: ', Hidden::VALUE, "\n";
        echo 'allowed-instanceof: ', (new Hidden() instanceof Hidden ? 'true' : 'false'), "\n";
        echo 'allowed-callable: ', (is_callable([Hidden::class, 'method']) ? 'true' : 'false'), "\n";
        echo 'allowed-call-user-func: ', call_user_func([Hidden::class, 'method']), "\n";
        class_alias(Hidden::class, __NAMESPACE__ . '\HiddenAlias');
        echo 'allowed-alias-use: ', get_class(new HiddenAlias()), "\n";
    }

    function newSecondAllowed(): object {
        return new SecondHidden();
    }
}

namespace Gate4\OpcacheCli\Consumer {
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

    echo "allowed-then-denied\n";
    \Gate4\OpcacheCli\Library\exerciseAllowed();
    report('denied-new', fn() => new \Gate4\OpcacheCli\Library\Hidden());
    report('denied-static-method', fn() => \Gate4\OpcacheCli\Library\Hidden::method());
    report('denied-static-property', fn() => \Gate4\OpcacheCli\Library\Hidden::$property);
    report('denied-constant', fn() => \Gate4\OpcacheCli\Library\Hidden::VALUE);
    report('denied-instanceof', fn() => new \stdClass() instanceof \Gate4\OpcacheCli\Library\Hidden);
    report('denied-callable', fn() => is_callable([\Gate4\OpcacheCli\Library\Hidden::class, 'method']));
    report('denied-call-user-func', fn() => call_user_func([\Gate4\OpcacheCli\Library\Hidden::class, 'method']));
    report('denied-alias-create', fn() => class_alias(\Gate4\OpcacheCli\Library\Hidden::class, __NAMESPACE__ . '\HiddenAlias'));
    report('denied-alias-use', fn() => new HiddenAlias());

    echo "denied-then-allowed\n";
    report('denied-new-second', fn() => new \Gate4\OpcacheCli\Library\SecondHidden());
    report('allowed-new-second-after-denied', fn() => \Gate4\OpcacheCli\Library\newSecondAllowed());
}

?>
--EXPECT--
allowed-then-denied
allowed-new: Gate4\OpcacheCli\Library\Hidden
allowed-static-method: method
allowed-static-property: property
allowed-static-property-write: changed
allowed-constant: constant
allowed-instanceof: true
allowed-callable: true
allowed-call-user-func: method
allowed-alias-use: Gate4\OpcacheCli\Library\Hidden
denied-new: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-static-method: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-static-property: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-constant: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-instanceof: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-callable: false
denied-call-user-func: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-alias-create: true
denied-alias-use: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\Hidden from namespace Gate4\OpcacheCli\Consumer
denied-then-allowed
denied-new-second: Error: Cannot access private(namespace) class Gate4\OpcacheCli\Library\SecondHidden from namespace Gate4\OpcacheCli\Consumer
allowed-new-second-after-denied: Gate4\OpcacheCli\Library\SecondHidden
