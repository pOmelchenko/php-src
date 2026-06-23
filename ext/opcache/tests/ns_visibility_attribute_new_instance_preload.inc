<?php

namespace Gate4\AttributePreload\Lib {
    #[\Attribute(\Attribute::TARGET_ALL)]
    private(namespace) class HiddenAttribute {
        public function __construct(public string $label) {
        }
    }

    #[\Attribute(\Attribute::TARGET_ALL)]
    protected(namespace) class ProtectedAttribute {
        public function __construct(public string $label) {
        }
    }

    #[HiddenAttribute('same-class')]
    class SameTarget {
    }
}

namespace Gate4\AttributePreload\Lib\Child {
    #[\Gate4\AttributePreload\Lib\ProtectedAttribute('child-class')]
    class ChildTarget {
    }
}

namespace Gate4\AttributePreload\Sibling {
    #[\Gate4\AttributePreload\Lib\ProtectedAttribute('sibling-class')]
    class SiblingTarget {
    }
}

namespace Gate4\AttributePreload\Consumer {
    #[\Gate4\AttributePreload\Lib\HiddenAttribute('denied-class')]
    class DeniedTarget {
    }
}

?>
