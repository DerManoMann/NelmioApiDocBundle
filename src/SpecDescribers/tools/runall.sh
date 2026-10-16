#!/bin/bash
# runall.sh <version> [oracle-dir] - every ControllerTest case through the PoC.
# Output (expected/actual per case) goes to $POC_OUT, default /tmp/poc-<version>.
cd "$(dirname "$0")/../../.."
export POC_VERSION=$1 POC_OUT=${POC_OUT:-/tmp/poc-$1}; mkdir -p "$POC_OUT"
[ -n "$2" ] && export POC_ORACLE=$2
while read -r c s; do
  [ -z "$c" ] && continue
  r=$(php src/SpecDescribers/run.php $c $s 2>&1 | grep "^==\|rror\]" | sed 's/ (\/[^)]*)//; s/^== //' | head -1)
  printf "%-60s %s\n" "$c $s" "${r#*: }"
done <<CASES
ApiController81Collisions
ContextAttributeController
Controller2209
CustomModelNameController
GenericTypesController
InvoiceDocumentController
InvokableController insecured
MapQueryParameterController
MapQueryStringController cleanup-components-type_info
MapQueryStringController MapQueryStringController-type_info
MapRequestPayloadArray
MapRequestPayloadController
MapUploadedFileController
MapUploadedFileWithRequestPayloadController
OpenApiTagController
OperationIdController
PromotedPropertiesController81 defaults
SecuredApiController api-key
SecuredApiController basic-auth
SecuredApiController bearer-auth
SecuredApiController cookie
SecuredApiController oauth2
SecuredApiController oauth2-multiple-flows
SecuredApiController openid-connect
SelfReferencingContextController
CASES
