<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Assembler;
use OpenApi\Contracts\ResolverInterface;
use OpenApi\Spec as OAS;

/**
 * Entity probe: describes a class no attribute describes, as nelmio's ObjectModelDescriber does
 * for an entity with only PHP types. A schema named after the class, a property per public
 * property, reflectors set, and the types left to swagger-php's Augmenter\Types.
 *
 * swagger-php has no such resolver (entity probe E1); Resolver\Reflection describes a class only
 * when it carries spec attributes. The members' own spec attributes are nested first, through the
 * assembler's public steps, so a class migrated from classic keeps what its members declare.
 */
final class PlainClassResolver implements ResolverInterface
{
    public function resolve(string $fqcn, Assembler $assembler): bool
    {
        if (!class_exists($fqcn) && !enum_exists($fqcn)) {
            return false;
        }

        $class = new \ReflectionClass($fqcn);
        // the project's own classes only: a vendor or internal class is the type layer's to map
        // (review 1: claiming Uuid or DateTimeZone here hid what swagger-php does with them)
        $file = $class->getFileName();
        if ($class->isInterface() || $class->isAbstract() || $class->isInternal()
            || false === $file || str_contains($file, '/vendor/')) {
            return false;
        }

        $schema = (new OAS\Schema(component: $class->getShortName()))->setReflector($class);
        if (!$class->isEnum()) {
            // what the members declare, nested as if the class carried #[Schema]: public API
            // (AttributeFactory::membersOf(), resolveHierarchy()), the assembler's own steps
            $factory = $assembler->getAttributeFactory();
            $factory->resolveHierarchy([$schema], $factory->membersOf($class));

            // and every other public property by its type, as nelmio describes them
            $declared = array_map(static fn (OAS\Property $p): ?string => $p->property ?? $p->getReflector()?->getName(), $schema->properties ?? []);
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic() && !\in_array($property->getName(), $declared, true)) {
                    $schema->properties = [...$schema->properties ?? [], (new OAS\Property(property: $property->getName()))->setReflector($property)];
                }
            }
        }

        $assembler->getSpecification()->add($schema);

        return true;
    }
}
