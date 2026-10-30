<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\ModelDescriber\Annotations;

use Nelmio\ApiDocBundle\Attribute\Spec\Model as SpecModel;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\Util\LegacyTypeConverter;
use OpenApi\Augmenter\Shortcuts;
use OpenApi\Compiler\OpenApi30Compiler;
use OpenApi\Compiler\OpenApi31Compiler;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Utils\AttributeFactory;

/**
 * SPIKE: spec attributes (`OpenApi\Spec`) on models, compiled per class or member and handed back
 * as arrays, for nelmio's own schema building to merge.
 *
 * @internal
 */
final class SpecModelAttributesReader
{
    private const FRAGMENT = '__nelmio_fragment';

    public function __construct(
        private readonly ModelRegistry $modelRegistry,
    ) {
    }

    /**
     * The class-level `#[OA\Schema]`, compiled, without its name.
     *
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return array<string, mixed>|null
     */
    public function getSchema(\ReflectionClass $reflectionClass, string $version, ?array $serializationGroups = null): ?array
    {
        foreach ((new AttributeFactory())->fromReflector($reflectionClass) as $attribute) {
            if ($attribute instanceof OAS\Schema) {
                $attribute->component = self::FRAGMENT;

                return $this->compile($attribute, $version, $serializationGroups);
            }
        }

        return null;
    }

    /**
     * @param \ReflectionProperty|\ReflectionMethod $reflection
     */
    public function getProperty($reflection): ?OAS\Property
    {
        foreach ((new AttributeFactory())->fromReflector($reflection) as $attribute) {
            if ($attribute instanceof OAS\Property) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * A member's `#[OA\Property]` schema, compiled.
     *
     * @param \ReflectionProperty|\ReflectionMethod $reflection
     * @param string[]|null                         $serializationGroups
     *
     * @return array<string, mixed>|null
     */
    public function getPropertySchema($reflection, string $version, ?array $serializationGroups = null): ?array
    {
        if (null === $property = $this->getProperty($reflection)) {
            return null;
        }
        // only the schema matters here, the name is nelmio's
        $property->property = self::FRAGMENT;

        $compiled = $this->compile(new OAS\Schema(component: self::FRAGMENT, properties: [$property]), $version, $serializationGroups)['properties'][self::FRAGMENT] ?? [];

        // an empty schema compiles to {}
        return \is_array($compiled) ? $compiled : [];
    }

    /**
     * @param string[]|null $serializationGroups
     *
     * @return array<string, mixed>
     */
    private function compile(OAS\Schema $schema, string $version, ?array $serializationGroups): array
    {
        $specification = (new Specification())->add($schema);
        (new Shortcuts())($specification);

        // a nested model without groups of its own takes the groups of the model it sits in, as with classic attributes
        $specification->getWalker()->visit(SpecModel::class, function (SpecModel $model) use ($serializationGroups): void {
            $definition = $model->getModel();
            $model->ref = $this->modelRegistry->register(new Model(
                LegacyTypeConverter::createType($definition['type']),
                $definition['groups'] ?? $serializationGroups,
                $definition['options'],
                $definition['serializationContext'],
                $definition['name'],
            ));
        });

        $compiler = str_starts_with($version, '3.0') ? new OpenApi30Compiler() : new OpenApi31Compiler();

        $compiled = $compiler->compile($specification)['components']['schemas'][self::FRAGMENT] ?? [];

        return \is_array($compiled) ? $compiled : [];
    }
}
