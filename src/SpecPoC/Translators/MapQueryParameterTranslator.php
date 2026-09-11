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
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use const FILTER_VALIDATE_EMAIL;

/**
 * Translate MapQueryParameter into `OA\Parameter\Query`
 */
class MapQueryParameterTranslator extends AbstractAttributeTranslator
{

    public function getAttributes(ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        if (!($reflector instanceof ReflectionParameter)) {
            return [];
        }

        return $reflector->getAttributes(
            MapQueryParameter::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );
    }

    public function translate(array $attributes, array $created, ReflectionClassConstant|ReflectionParameter|ReflectionMethod|ReflectionClass|ReflectionProperty $reflector): array
    {
        $processed = $attributes;

        foreach ($created as $attribute) {
            if ($attribute instanceof MapQueryParameter) {
                $schema = null;
                $format = $attribute->filter;
                if ($format !== null) {
                    $schema ??= new OA\Schema();
                    $schema->format = $format === FILTER_VALIDATE_EMAIL ? 'email' : null;
                }

                $processed[] = new OA\Parameter\Query(
                    name: $reflector->getName(),
                    schema: $schema,
                );
            }
        }

        return $processed;
    }
}
