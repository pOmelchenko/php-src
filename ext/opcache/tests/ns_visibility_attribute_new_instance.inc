<?php

namespace Gate4\AttributeOpcache\Lib {
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

namespace Gate4\AttributeOpcache\Lib\Child {
    #[\Gate4\AttributeOpcache\Lib\ProtectedAttribute('child-class')]
    class ChildTarget {
    }
}

namespace Gate4\AttributeOpcache\Sibling {
    #[\Gate4\AttributeOpcache\Lib\ProtectedAttribute('sibling-class')]
    class SiblingTarget {
    }
}

namespace Gate4\AttributeOpcache\Consumer {
    #[\Gate4\AttributeOpcache\Lib\HiddenAttribute('denied-class')]
    class DeniedTarget {
    }

    function report(string $label, \ReflectionAttribute $attribute): void {
        try {
            $object = $attribute->newInstance();
            echo $label, ': ', $object->label, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('same-class',
        (new \ReflectionClass(\Gate4\AttributeOpcache\Lib\SameTarget::class))->getAttributes()[0]);
    report('denied-class',
        (new \ReflectionClass(DeniedTarget::class))->getAttributes()[0]);
    report('protected-child',
        (new \ReflectionClass(\Gate4\AttributeOpcache\Lib\Child\ChildTarget::class))->getAttributes()[0]);
    report('protected-sibling',
        (new \ReflectionClass(\Gate4\AttributeOpcache\Sibling\SiblingTarget::class))->getAttributes()[0]);
}

?>
