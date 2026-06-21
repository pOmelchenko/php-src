#include "php.h"
#include "Zend/zend_compile.h"
#include "Zend/zend.h"

#include <stdio.h>

int main(void)
{
	printf("{\n");
	printf("  \"sizeof_zend_class_entry\": %zu,\n", sizeof(zend_class_entry));
	printf("  \"sizeof_zend_op_array\": %zu,\n", sizeof(zend_op_array));
#ifdef ZEND_ACC2_NAMESPACE_RESTRICTED
	printf("  \"sizeof_zend_op_array_namespace_range\": %zu,\n", sizeof(zend_op_array_namespace_range));
	printf("  \"namespace_visibility_supported\": true\n");
#else
	printf("  \"sizeof_zend_op_array_namespace_range\": null,\n");
	printf("  \"namespace_visibility_supported\": false\n");
#endif
	printf("}\n");
	return 0;
}
