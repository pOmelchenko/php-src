<?php

namespace Gate4\PreloadLink\Hidden {
    private(namespace) class ParentClass {}
    private(namespace) interface Contract {}
    private(namespace) interface BaseContract {}
    private(namespace) trait HelperTrait {}
}

namespace Gate4\PreloadLink\Denied {
    class BadParent extends \Gate4\PreloadLink\Hidden\ParentClass {}
    class BadImplementer implements \Gate4\PreloadLink\Hidden\Contract {}
    interface BadInterface extends \Gate4\PreloadLink\Hidden\BaseContract {}
    class BadTraitUse {
        use \Gate4\PreloadLink\Hidden\HelperTrait;
    }
}

?>
