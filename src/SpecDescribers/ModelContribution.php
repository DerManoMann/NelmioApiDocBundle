<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Model\ModelRegistry;
use OpenApi\Annotations as OA;
use OpenApi\Contracts\AttributeInterface;
use OpenApi\Spec as OAS;
use OpenApi\Specification;

/**
 * Hands every #[Model] in the specification to nelmio's ModelRegistry, which names it, and lets
 * nelmio's model describers describe the lot. The described schemas are contributed as components.
 *
 * Runs after DescriberContribution with the same registry, so a model a route argument describer
 * registered and one an attribute names share a component, as in nelmio.
 */
final class ModelContribution
{
    private const MEDIA_TYPES = ['json' => 'application/json', 'xml' => 'application/xml'];

    /**
     * @param list<string> $mediaTypes nelmio's `media_types` config
     */
    public function __construct(
        private readonly ModelRegistry $modelRegistry,
        private readonly OA\OpenApi $scratch,
        private readonly array $mediaTypes = ['json'],
        private readonly DocumentBridge $bridge = new DocumentBridge(),
    ) {
    }

    public function __invoke(Specification $specification): void
    {
        $specification->getWalker()->eachRef(function (AttributeInterface $attribute): void {
            if ($attribute->ref instanceof Model) {
                $attribute->ref = $this->modelRegistry->register($attribute->ref->toRegistryModel());
            }
        });

        // #[Response|RequestBody|Parameter(attachables: [new Model()])]: nelmio's implicit form
        $specification->getWalker()->visit(AttributeInterface::class, function (AttributeInterface $attribute): void {
            if (!$attribute instanceof OAS\Response && !$attribute instanceof OAS\RequestBody && !$attribute instanceof OAS\Parameter) {
                return;
            }
            foreach ($attribute->attachables ?? [] as $i => $model) {
                if ($model instanceof Model) {
                    unset($attribute->attachables[$i]);
                    $attribute->attachables = array_values($attribute->attachables) ?: null;
                    $this->attach($attribute, $this->modelRegistry->register($model->toRegistryModel()));
                    break;
                }
            }
        });

        $this->modelRegistry->registerSchemas();

        if ($this->scratch->components instanceof OA\Components && \is_array($this->scratch->components->schemas)) {
            foreach ($this->scratch->components->schemas as $schema) {
                $specification->add($this->bridge->schema($schema));
            }
        }
    }

    private function attach(OAS\Response|OAS\RequestBody|OAS\Parameter $attribute, string $ref): void
    {
        if ($attribute instanceof OAS\Parameter) {
            $attribute->schema = 'array' === $attribute->schema?->type
                ? new OAS\Schema(type: 'array', items: new OAS\Schema\Items(ref: $ref))
                : new OAS\Schema(ref: $ref);

            return;
        }

        foreach ($this->mediaTypes as $mediaType) {
            $attribute->content = [...$attribute->content ?? [], new OAS\MediaType(mediaType: self::MEDIA_TYPES[$mediaType], schema: new OAS\Schema(ref: $ref))];
        }
    }
}
