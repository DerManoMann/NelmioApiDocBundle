<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;

/**
 * WORKAROUND (loopback L5): swagger-php expands `{method}` in an explicit operationId only in
 * Augmenter\OperationIds, which also generates ids. nelmio removes that augmenter, so this does the
 * expansion alone.
 *
 * @implements PipeInterface<Specification>
 */
final class OperationIdTemplateAugmenter implements PipeInterface
{
    public function __invoke(mixed $payload): mixed
    {
        foreach ($payload->operations as $operation) {
            if (null !== $operation->operationId && null !== $operation->method) {
                $operation->operationId = strtr($operation->operationId, ['{method}' => $operation->method]);
            }
        }

        return null;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Augment;
    }
}
