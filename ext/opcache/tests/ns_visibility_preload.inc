<?php

namespace Gate4\Preload\Library;

private(namespace) class Hidden {
    public static string $property = 'property';
    public const VALUE = 'value';

    public static function label(): string {
        return 'hidden';
    }

    public function instanceMethod(): string {
        return 'instance';
    }
}

private(namespace) interface HiddenInterface {}

private(namespace) trait HiddenTrait {
    public function traitLabel(): string {
        return 'trait';
    }
}

private(namespace) enum HiddenEnum {
    case CaseA;
}

class PublicFactory {
    public static function make(): Hidden {
        return new Hidden();
    }
}

?>
