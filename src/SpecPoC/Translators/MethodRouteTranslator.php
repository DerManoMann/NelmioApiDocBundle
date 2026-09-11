<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Translators;

use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Spec as OA;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Translate method level Route attributes onto operations.
 */
class MethodRouteTranslator extends AbstractAttributeTranslator
{

    public function getAttributes(ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        if (!($reflector instanceof ReflectionMethod)) {
            return [];
        }

        return $reflector->getAttributes(
            Route::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );

    }

    public function translate(array $attributes, array $created, ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        $processed = $attributes;

        foreach ($created as $attribute) {
            if ($attribute instanceof Route) {
                $processed[] = new OA\Operation(
                    path: $attribute->path,
                    method: $attribute->methods[0],
                );
            }
        }

        return $processed;
    }
}
