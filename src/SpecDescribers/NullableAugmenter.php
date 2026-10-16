<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;

/**
 * nelmio's NullablePropertyProcessor: `nullable: false` on a component's property is left out.
 */
final class NullableAugmenter implements PipeInterface
{
    /**
     * @param Specification $specification
     */
    public function __invoke(mixed $specification): mixed
    {
        foreach ($specification->schemas as $schema) {
            foreach ($schema->properties ?? [] as $property) {
                if (false === $property->schema?->nullable) {
                    $property->schema->nullable = null;
                }
            }
        }

        return $specification;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Reduce;
    }
}
