<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Attribute\Operation as NelmioOperation;
use OpenApi\Contracts\AttributeTranslatorInterface;
use OpenApi\Generator;
use OpenApi\Spec as OAS;

/**
 * nelmio's own #[Operation([...])]: what it declares applies to every operation of the route,
 * whatever the method. It is a classic annotation, so the spec pipeline does not read it.
 * Registered after RouteOperationTranslator, so the operations exist.
 */
final class NelmioOperationTranslator implements AttributeTranslatorInterface
{
    public function reset(): void
    {
    }

    public function getAttributes(\ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
    {
        return $reflector instanceof \ReflectionMethod ? $reflector->getAttributes(NelmioOperation::class) : [];
    }

    public function translate(array $attributes, array $created, \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
    {
        foreach ($created as $nelmio) {
            if (!$nelmio instanceof NelmioOperation) {
                continue;
            }
            foreach ($attributes as $operation) {
                if (!$operation instanceof OAS\Operation) {
                    continue;
                }
                foreach (['responses', 'parameters'] as $list) {
                    if (\is_array($nelmio->{$list})) {
                        $operation->{$list} = [...$operation->{$list} ?? [], ...array_map(static fn ($a) => clone $a, $nelmio->{$list})];
                    }
                }
                foreach (['summary', 'description', 'deprecated', 'operationId'] as $field) {
                    if (Generator::UNDEFINED !== $nelmio->{$field} && null === ($operation->{$field} ?? null)) {
                        $operation->{$field} = $nelmio->{$field};
                    }
                }
                if (\is_array($nelmio->tags)) {
                    $operation->tags = array_values(array_unique([...$operation->tags ?? [], ...$nelmio->tags]));
                }
            }
        }

        return $attributes;
    }
}
