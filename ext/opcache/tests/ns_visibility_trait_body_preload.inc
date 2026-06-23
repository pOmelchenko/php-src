<?php

namespace Gate4\TraitBodyPreload\App {
    trait DeniedTrait {
        public function make(): object {
            return new \Gate4\TraitBodyPreload\Library\Secret();
        }
    }
}

namespace Gate4\TraitBodyPreload\Library {
    private(namespace) class Secret {}

    trait AllowedTrait {
        public function make(): object {
            return new Secret();
        }
    }

    class UsesExternalTrait {
        use \Gate4\TraitBodyPreload\App\DeniedTrait;
    }
}

?>
