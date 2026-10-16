<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Contracts\AttributeTranslatorInterface;
use OpenApi\Spec as OAS;

/**
 * nelmio's #[Security] on a route method: a requirement on each of its operations, or none with
 * no name. Registered after RouteOperationTranslator, so the operations already exist.
 */
final class SecurityTranslator implements AttributeTranslatorInterface
{
    /** @var list<Security> the class's own, applied to each of its methods' operations */
    private array $classSecurity = [];

    public function reset(): void
    {
        $this->classSecurity = [];
    }

    public function getAttributes(\ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
    {
        return $reflector instanceof \ReflectionMethod || $reflector instanceof \ReflectionClass
            ? $reflector->getAttributes(Security::class)
            : [];
    }

    public function translate(array $attributes, array $created, \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
    {
        $securities = array_values(array_filter($created, static fn ($c) => $c instanceof Security));
        if ($reflector instanceof \ReflectionClass) {
            // reset() runs per class, before its own reflector and then its members'
            $this->classSecurity = $securities;
        } else {
            $securities = [...$this->classSecurity, ...$securities];
        }

        foreach ($securities as $security) {
            foreach ($attributes as $operation) {
                if ($operation instanceof OAS\Operation) {
                    $operation->security = null === $security->name
                        ? []
                        : [...$operation->security ?? [], new OAS\Security\Requirement(scheme: $security->name, scopes: $security->scopes)];
                }
            }
        }

        return $attributes;
    }
}
