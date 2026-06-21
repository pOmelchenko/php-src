<?php

namespace Gate4\FileCache\Library {
    private(namespace) class Hidden {
        public static function label(): string {
            return 'hidden';
        }

        public function instanceMethod(): string {
            return 'instance';
        }
    }

    function make(): Hidden {
        return new Hidden();
    }

    $reflection = new \ReflectionClass(Hidden::class);
    echo 'metadata: private=', $reflection->isNamespacePrivate() ? 'true' : 'false',
        ' protected=', $reflection->isNamespaceProtected() ? 'true' : 'false',
        ' root=', $reflection->getNamespaceVisibilityRoot(), "\n";
    echo 'library-1: ', Hidden::label(), "\n";
}

namespace Gate4\FileCache\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            if (is_object($value)) {
                $value = get_class($value);
            }
            echo $label, ': ', $value === null ? 'ok' : $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('consumer-new', fn() => new \Gate4\FileCache\Library\Hidden());
    report('consumer-static', fn() => \Gate4\FileCache\Library\Hidden::label());
    report('consumer-instanceof', fn() => new \stdClass() instanceof \Gate4\FileCache\Library\Hidden);

    $object = \Gate4\FileCache\Library\make();
    report('consumer-object-class', fn() => get_class($object));
    report('consumer-object-method', fn() => $object->instanceMethod());
}

namespace Gate4\FileCache\Library {
    echo 'library-2: ', Hidden::label(), "\n";
}

?>
