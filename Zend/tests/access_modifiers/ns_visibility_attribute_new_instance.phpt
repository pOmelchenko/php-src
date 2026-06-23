--TEST--
Namespace visibility for ReflectionAttribute::newInstance()
--FILE--
<?php

namespace AttrVisibility\Lib {
    #[\Attribute(\Attribute::TARGET_ALL)]
    private(namespace) class HiddenAttribute {
        public static int $constructed = 0;

        public function __construct(public string $label) {
            self::$constructed++;
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

    function hidden_constructed(): int {
        return HiddenAttribute::$constructed;
    }
}

namespace AttrVisibility\Lib\Child {
    #[\AttrVisibility\Lib\ProtectedAttribute('child-class')]
    class ChildTarget {
    }
}

namespace AttrVisibility\Sibling {
    #[\AttrVisibility\Lib\ProtectedAttribute('sibling-class')]
    class SiblingTarget {
    }
}

namespace AttrVisibility\Consumer {
    #[\AttrVisibility\Lib\HiddenAttribute('class')]
    class DeniedClass {
        #[\AttrVisibility\Lib\HiddenAttribute('class-constant')]
        public const VALUE = 1;

        #[\AttrVisibility\Lib\HiddenAttribute('property')]
        public int $property = 1;

        #[\AttrVisibility\Lib\HiddenAttribute('method')]
        public function method(
            #[\AttrVisibility\Lib\HiddenAttribute('parameter')]
            int $parameter
        ): void {
        }
    }

    #[\AttrVisibility\Lib\HiddenAttribute('function')]
    function denied_function(): void {
    }

    #[\AttrVisibility\Lib\HiddenAttribute('constant')]
    const DENIED_CONST = 1;

    function report(string $label, \ReflectionAttribute $attribute): void {
        echo $label, '-name: ', $attribute->getName(), "\n";

        try {
            $object = $attribute->newInstance();
            echo $label, ': ', $object->label, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('same-class-from-foreign-namespace',
        (new \ReflectionClass(\AttrVisibility\Lib\SameTarget::class))->getAttributes()[0]);
    report('denied-class',
        (new \ReflectionClass(DeniedClass::class))->getAttributes()[0]);
    report('denied-function',
        (new \ReflectionFunction(__NAMESPACE__ . '\\denied_function'))->getAttributes()[0]);
    report('denied-method',
        (new \ReflectionMethod(DeniedClass::class, 'method'))->getAttributes()[0]);
    report('denied-parameter',
        (new \ReflectionParameter([DeniedClass::class, 'method'], 'parameter'))->getAttributes()[0]);
    report('denied-property',
        (new \ReflectionProperty(DeniedClass::class, 'property'))->getAttributes()[0]);
    report('denied-class-constant',
        (new \ReflectionClassConstant(DeniedClass::class, 'VALUE'))->getAttributes()[0]);
    report('denied-constant',
        (new \ReflectionConstant(__NAMESPACE__ . '\\DENIED_CONST'))->getAttributes()[0]);
    report('protected-child',
        (new \ReflectionClass(\AttrVisibility\Lib\Child\ChildTarget::class))->getAttributes()[0]);
    report('protected-sibling',
        (new \ReflectionClass(\AttrVisibility\Sibling\SiblingTarget::class))->getAttributes()[0]);

    echo 'hidden-constructed: ', \AttrVisibility\Lib\hidden_constructed(), "\n";
}

?>
--EXPECT--
same-class-from-foreign-namespace-name: AttrVisibility\Lib\HiddenAttribute
same-class-from-foreign-namespace: same-class
denied-class-name: AttrVisibility\Lib\HiddenAttribute
denied-class: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
denied-function-name: AttrVisibility\Lib\HiddenAttribute
denied-function: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
denied-method-name: AttrVisibility\Lib\HiddenAttribute
denied-method: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
denied-parameter-name: AttrVisibility\Lib\HiddenAttribute
denied-parameter: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
denied-property-name: AttrVisibility\Lib\HiddenAttribute
denied-property: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
denied-class-constant-name: AttrVisibility\Lib\HiddenAttribute
denied-class-constant: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
denied-constant-name: AttrVisibility\Lib\HiddenAttribute
denied-constant: Error: Cannot access private(namespace) class AttrVisibility\Lib\HiddenAttribute from namespace AttrVisibility\Consumer
protected-child-name: AttrVisibility\Lib\ProtectedAttribute
protected-child: child-class
protected-sibling-name: AttrVisibility\Lib\ProtectedAttribute
protected-sibling: Error: Cannot access protected(namespace) class AttrVisibility\Lib\ProtectedAttribute from namespace AttrVisibility\Sibling
hidden-constructed: 1
