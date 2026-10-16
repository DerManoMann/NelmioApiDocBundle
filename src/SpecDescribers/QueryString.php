<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Spec\Attachable;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * What nelmio's MapQueryString describer leaves in the classic operation's _context, as an attachable.
 */
final class QueryString extends Attachable
{
    public function __construct(
        public readonly ArgumentMetadata $argument,
        public readonly string $modelRef,
    ) {
        parent::__construct();
    }

    public function isRoot(): bool
    {
        return false;
    }
}
