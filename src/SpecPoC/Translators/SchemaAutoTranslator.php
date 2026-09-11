<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Translators;

use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Spec as OA;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Fallback for unresolved schema/property candidates.
 */
class SchemaAutoTranslator extends AbstractAttributeTranslator
{
    public function translate(array $attributes, array $created, ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        $processed = parent::translate($attributes, $created, $reflector);

        if ($reflector instanceof ReflectionClass) {
            $processed[] = new OA\Schema();
        }
        if ($reflector instanceof ReflectionProperty && $reflector->isPublic()) {
            $processed[] = new OA\Property();
        }
        if ($reflector instanceof ReflectionParameter) {
            $method = $reflector->getDeclaringFunction();
            if ($method->getName() === '__construct') {
                $processed[] = new OA\Property();
            }
        }

        return $processed;
    }
}
