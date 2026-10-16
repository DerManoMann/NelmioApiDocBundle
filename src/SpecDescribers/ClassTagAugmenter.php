<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;

/**
 * nelmio semantics: a #[Tag] on a controller class tags every operation routed to it, inherited
 * methods included.
 * Runs before Augmenter\Tags, which drops tags nothing uses.
 */
final class ClassTagAugmenter implements PipeInterface
{
    /**
     * @param Specification $specification
     */
    public function __invoke(mixed $specification): mixed
    {
        $byClass = [];
        foreach ($specification->tags as $tag) {
            $reflector = $tag->getReflector();
            if ($reflector instanceof \ReflectionClass && null !== $tag->name) {
                $byClass[$reflector->getName()][] = $tag->name;
            }
        }

        foreach ($specification->operations as $operation) {
            $controller = $operation->getMeta(RouteOperationTranslator::META_CONTROLLER);
            if (null !== $controller && isset($byClass[$controller])) {
                $operation->tags = array_values(array_unique([...$operation->tags ?? [], ...$byClass[$controller]]));
            }
        }

        return $specification;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Reduce;
    }
}
