<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Augmenter;

use BackedEnum;
use Nelmio\ApiDocBundle\SpecPoC\Attributes\ContentModel;
use Nelmio\ApiDocBundle\SpecPoC\Attributes\RefModel;
use OpenApi\Assembler;
use OpenApi\Augmenter\Group;
use OpenApi\Contracts\AttributeInterface;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * Augment RefModel and ContentModel refs
 */
final class ModelAugmenter implements PipeInterface
{
    private array $collected = [];

    public function __construct(protected Assembler $assembler)
    {
    }

    public function __invoke(mixed $payload): mixed
    {
        $this->augmentRefs($payload);
        $this->augmentRequestBodyContent($payload);

        return null;
    }

    public function group(): string|BackedEnum
    {
        return Group::Resolve;
    }

    protected function augmentRefs(Specification $specification): void
    {
        $specification->getWalker()->eachRef(function (AttributeInterface $attribute) use ($specification): void {
            if ($attribute->ref instanceof RefModel) {
                $this->collectModel($attribute->ref->ref, $specification);
                $attribute->ref = $attribute->ref->ref;
            }
        });

        $schemas = $this->assembler->getSpecification()->schemas;
        $specification->add(...$schemas);
        $this->assembler->getSpecification()->schemas = [];
    }

    private function collectModel(string $fqcn, Specification $specification): void
    {
        if (isset($this->collected[$fqcn])) {
            return;
        }
        $this->collected[$fqcn] = true;

        $this->assembler->collect(new ReflectionClass($fqcn));
        $this->collectReferencedTypes(new ReflectionClass($fqcn), $specification);
    }

    private function collectReferencedTypes(ReflectionClass $reflector, Specification $specification): void
    {
        foreach ($this->getTypedReflectors($reflector) as $type) {
            if (!$type->isBuiltin()) {
                $this->collectModel($type->getName(), $specification);
            }
        }
    }

    /**
     * @return list<ReflectionNamedType>
     */
    private function getTypedReflectors(ReflectionClass $reflector): array
    {
        $types = [];

        foreach ($reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->getDeclaringClass()->getName() !== $reflector->getName()) {
                continue;
            }
            foreach ($this->extractNamedTypes($prop->getType()) as $type) {
                $types[] = $type;
            }
        }

        if ($constructor = $reflector->getConstructor()) {
            foreach ($constructor->getParameters() as $param) {
                foreach ($this->extractNamedTypes($param->getType()) as $type) {
                    $types[] = $type;
                }
            }
        }

        return $types;
    }

    /**
     * @return list<ReflectionNamedType>
     */
    private function extractNamedTypes(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type];
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $named = [];
            foreach ($type->getTypes() as $inner) {
                if ($inner instanceof ReflectionNamedType) {
                    $named[] = $inner;
                }
            }
            return $named;
        }

        return [];
    }

    protected function augmentRequestBodyContent(Specification $specification): void
    {
        $specification->getWalker()->visit(OA\RequestBody::class, function (OA\RequestBody $requestBody): void {
            // RequestBody::$content is wrapped into a list
            foreach ($requestBody->content ?? [] as $ii => $mediaType) {
                if ($mediaType instanceof ContentModel) {
                    $requestBody->content[$ii] = new OA\MediaType\Json(
                        ref: $mediaType->ref,
                    );
                    // do more stuff...
                }
            }
        });
    }
}
