<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Augmenter;

use Nelmio\ApiDocBundle\Attribute\Operation;
use Nelmio\ApiDocBundle\SpecPoC\Attributes\OperationAttachable;
use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use OpenApi\Spec as OA;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class MapRequestPayloadAugmenter implements PipeInterface
{

    public function __invoke(mixed $payload): mixed
    {
        $this->augmentOperationMapRequestPayload($payload);

        return null;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Resolve;
    }

    protected function augmentOperationMapRequestPayload(Specification $specification): void
    {
        $specification->getWalker()->visit(OA\Operation::class, function (OA\Operation $operation) {
            if ($operation->attachables) {
                foreach ($operation->attachables as $attachable) {
                    if ($attachable instanceof OperationAttachable) {
                        $this->augmentOperationRequestBody($operation, $attachable->mapRequestPayload);
                    }
                }
            }
        });
    }

    protected function augmentOperationRequestBody(OA\Operation $operation, MapRequestPayload $mapRequestPayload): void
    {
        // might already exist, that's why the indirection with OperationAttachable
        $operation->requestBody ??= new OA\RequestBody();
        // do stuff with mapRequestPayload
    }
}
