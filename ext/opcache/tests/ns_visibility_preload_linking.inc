<?php

namespace Gate4\PreloadLink\Allowed {
    private(namespace) class ParentClass {
        public static function parentLabel(): string {
            return 'allowed-parent';
        }
    }

    private(namespace) interface Contract {
        public function contract(): string;
    }

    private(namespace) interface BaseContract {}

    private(namespace) trait HelperTrait {
        public function helper(): string {
            return 'helper';
        }
    }

    class Child extends ParentClass {}

    class Implementer implements Contract {
        public function contract(): string {
            return 'contract';
        }
    }

    interface ChildContract extends BaseContract {}

    class TraitUser {
        use HelperTrait;
    }
}

namespace {
    opcache_compile_file(__DIR__ . '/ns_visibility_preload_linking_denied.inc');
}

?>
