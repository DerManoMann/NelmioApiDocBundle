<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Spec\Attachable;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * What nelmio's MapRequestPayload describer leaves in the classic operation's _context, carried as
 * an attachable: meta does not survive Merge\Operations for the later operation, attachables do.
 */
final class RequestPayload extends Attachable
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
