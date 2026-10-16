<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;

/**
 * nelmio's DefaultDescriber: an empty info, and a `default` response on any operation without one.
 * It needs the merged result, so it is an augmenter rather than a contribution.
 */
final class DefaultsAugmenter implements PipeInterface
{
    /**
     * @param Specification $specification
     */
    public function __invoke(mixed $specification): mixed
    {
        $specification->info ??= new OAS\Info(title: '', version: '0.0.0');

        foreach ($specification->operations as $operation) {
            if ([] === ($operation->responses ?? [])) {
                $operation->responses = [new OAS\Response(response: 'default', description: '')];
            }
        }

        return $specification;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Reduce;
    }
}
