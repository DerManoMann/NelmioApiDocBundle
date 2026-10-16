<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * nelmio's MapRequestPayloadProcessor, the common part: once explicit attributes and describer
 * output have merged, the body is required unless the argument is optional, and a body without
 * content gets the payload model in each accepted format. (The multipart allOf rewrite is not ported.)
 */
final class RequestPayloadAugmenter implements PipeInterface
{
    private const FORMATS = ['json' => 'application/json', 'xml' => 'application/xml'];

    /**
     * @param Specification $specification
     */
    public function __invoke(mixed $specification): mixed
    {
        foreach ($specification->operations as $operation) {
            foreach ($operation->attachables ?? [] as $payload) {
                if ($payload instanceof RequestPayload) {
                    $this->complete($operation, $payload);
                }
            }
        }

        return $specification;
    }

    private function complete(OAS\Operation $operation, RequestPayload $payload): void
    {
        $argument = $payload->argument;
        $body = $operation->requestBody ??= new OAS\RequestBody();
        $body->required ??= !($argument->hasDefaultValue() || $argument->isNullable());

        if (null !== $body->content && [] !== $body->content) {
            return;
        }

        $attribute = $argument->getAttributes(MapRequestPayload::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;
        $formats = (array) ($attribute?->acceptFormat ?? 'json');
        $schema = 'array' === $argument->getType()
            ? new OAS\Schema(type: 'array', items: new OAS\Schema\Items(ref: $payload->modelRef))
            : new OAS\Schema(ref: $payload->modelRef);
        if ($argument->isNullable()) {
            $schema->nullable = true;
        }

        $body->content = array_map(
            static fn (string $format) => new OAS\MediaType(mediaType: self::FORMATS[$format], schema: clone $schema),
            array_values($formats),
        );
    }

    /**
     * Inserted before Augmenter\Cleanup: the refs it adds must count, or Cleanup removes the
     * components they point at (finding 12).
     */
    public function group(): string|\BackedEnum
    {
        return Group::Reduce;
    }
}
