--TEST--
Namespace visibility comparison follows class-name case-insensitivity
--FILE--
<?php

namespace Acme\Billing {
    private(namespace) class CaseService {
        public function label(): string { return 'case'; }
    }
}

namespace acme\billing {
    function newFromDifferentCaseNamespace(): object {
        return new \Acme\Billing\CaseService();
    }
}

namespace {
    echo \acme\billing\newFromDifferentCaseNamespace()->label(), "\n";
}

?>
--EXPECT--
case
