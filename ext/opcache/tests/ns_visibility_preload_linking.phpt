--TEST--
Namespace visibility is enforced while preloading links dependencies
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.preload={PWD}/ns_visibility_preload_linking.inc
opcache.jit=0
--EXTENSIONS--
opcache
--SKIPIF--
<?php
if (PHP_OS_FAMILY == 'Windows') die('skip Preloading is not supported on Windows');
if (getenv('SKIP_ASAN')) die('xleak Startup failure leak');
?>
--FILE--
<?php

namespace Gate4\PreloadLink\Allowed {
    echo 'allowed-parent: ', Child::parentLabel(), "\n";
    echo 'allowed-implements: ', (new Implementer())->contract(), "\n";
    echo 'allowed-interface-extends: ', interface_exists(ChildContract::class) ? 'yes' : 'no', "\n";
    echo 'allowed-trait: ', (new TraitUser())->helper(), "\n";
}

namespace Gate4\PreloadLink\Denied {
    foreach ([
        'bad-parent' => BadParent::class,
        'bad-implements' => BadImplementer::class,
        'bad-interface-extends' => BadInterface::class,
        'bad-trait-use' => BadTraitUse::class,
    ] as $label => $class) {
        echo $label, ': ', class_exists($class, false) || interface_exists($class, false) ? 'usable' : 'not-usable', "\n";
    }
}

?>
--EXPECTF--
Warning: Can't preload unlinked class Gate4\PreloadLink\Denied\BadParent: Cannot access private(namespace) class Gate4\PreloadLink\Hidden\ParentClass from namespace Gate4\PreloadLink\Denied in %sns_visibility_preload_linking_denied.inc on line %d

Warning: Can't preload unlinked class Gate4\PreloadLink\Denied\BadImplementer: Cannot access private(namespace) interface Gate4\PreloadLink\Hidden\Contract from namespace Gate4\PreloadLink\Denied in %sns_visibility_preload_linking_denied.inc on line %d

Warning: Can't preload unlinked class Gate4\PreloadLink\Denied\BadInterface: Cannot access private(namespace) interface Gate4\PreloadLink\Hidden\BaseContract from namespace Gate4\PreloadLink\Denied in %sns_visibility_preload_linking_denied.inc on line %d

Warning: Can't preload unlinked class Gate4\PreloadLink\Denied\BadTraitUse: Cannot access private(namespace) trait Gate4\PreloadLink\Hidden\HelperTrait from namespace Gate4\PreloadLink\Denied in %sns_visibility_preload_linking_denied.inc on line %d
allowed-parent: allowed-parent
allowed-implements: contract
allowed-interface-extends: yes
allowed-trait: helper
bad-parent: not-usable
bad-implements: not-usable
bad-interface-extends: not-usable
bad-trait-use: not-usable
