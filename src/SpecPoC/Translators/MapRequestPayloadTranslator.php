<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Translators;

use Nelmio\ApiDocBundle\SpecPoC\Attributes\OperationAttachable;
use Nelmio\ApiDocBundle\SpecPoC\Augmenter\MapRequestPayloadAugmenter;
use OpenApi\Assembler\AbstractAttributeTranslator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * Translate MapRequestPayload into `OperationAttachable` - defer processing into MapRequestPayloadAugmenter.
 *
 * This cannot be resolved here as the parent might still get a native `OA\RequestBody` attached.
 */
class MapRequestPayloadTranslator extends AbstractAttributeTranslator
{
    public function getAttributes(ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        if (!($reflector instanceof ReflectionParameter)) {
            return [];
        }

        return $reflector->getAttributes(
            MapRequestPayload::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );
    }

    public function translate(array $attributes, array $created, ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        $processed = $attributes;

        foreach ($created as $attribute) {
            if ($attribute instanceof MapRequestPayload) {
                // tack onto operation
                $processed[] = new OperationAttachable(
                    mapRequestPayload: $attribute,
                );
            }
        }

        return $processed;
    }
}
