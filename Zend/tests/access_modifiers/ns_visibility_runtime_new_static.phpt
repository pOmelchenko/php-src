--TEST--
Namespace visibility runtime checks for static new
--FILE--
<?php

namespace Acme\Billing {
    private(namespace) class PrivateService {
        public function label(): string { return 'private'; }
    }

    protected(namespace) class ProtectedService {
        public function label(): string { return 'protected'; }
    }

    function newPrivate(): object {
        return new PrivateService();
    }

    function newProtected(): object {
        return new ProtectedService();
    }
}

namespace Acme\Billing\Application {
    function newPrivateFromChild(): object {
        return new \Acme\Billing\PrivateService();
    }

    function newProtectedFromChild(): object {
        return new \Acme\Billing\ProtectedService();
    }
}

namespace Acme\Other {
    function newProtectedFromSibling(): object {
        return new \Acme\Billing\ProtectedService();
    }
}

namespace {
    echo \Acme\Billing\newPrivate()->label(), "\n";
    echo \Acme\Billing\newProtected()->label(), "\n";
    echo \Acme\Billing\Application\newProtectedFromChild()->label(), "\n";

    foreach ([
        \Acme\Billing\Application\newPrivateFromChild(...),
        \Acme\Other\newProtectedFromSibling(...),
    ] as $callback) {
        try {
            $callback();
        } catch (Error $e) {
            echo get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
}

?>
--EXPECT--
private
protected
protected
Error: Cannot access private(namespace) class Acme\Billing\PrivateService from namespace Acme\Billing\Application
Error: Cannot access protected(namespace) class Acme\Billing\ProtectedService from namespace Acme\Other
