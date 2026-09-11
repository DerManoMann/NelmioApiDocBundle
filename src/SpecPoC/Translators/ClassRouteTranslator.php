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
 * Translate Symfony Route attributes on class level.
 *
 * Either an invokable controller, or class level similar to the spec pipeline PathItem nesting.
 */
class ClassRouteTranslator extends AbstractAttributeTranslator
{

    public function getAttributes(ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        if (!($reflector instanceof ReflectionClass)) {
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
                if ($reflector->hasMethod('__invoke')) {
                    $processed[] = new OA\Operation(
                        path: $attribute->path,
                        method: $attribute->methods[0],
                    );
                } else {
                    $processed[] = new OA\PathItem(
                        prefix: $attribute->path,
                    );
                }
            }
        }

        return $processed;
    }
}
