--TEST--
Namespace visibility modifier tokens
--EXTENSIONS--
tokenizer
--FILE--
<?php

$code = '<?php private(namespace) class A {} protected(namespace) interface I {}';
foreach (token_get_all($code) as $token) {
    if (is_array($token) && $token[0] !== T_WHITESPACE) {
        echo token_name($token[0]), ': ', $token[1], "\n";
    }
}

?>
--EXPECT--
T_OPEN_TAG: <?php 
T_PRIVATE_NAMESPACE: private(namespace)
T_CLASS: class
T_STRING: A
T_PROTECTED_NAMESPACE: protected(namespace)
T_INTERFACE: interface
T_STRING: I
