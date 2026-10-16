<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Undefined;
use OpenApi\Utils\PipeInterface;

/**
 * nelmio's MapQueryStringProcessor: the properties of a #[MapQueryString] model become query
 * parameters. Where a parameter of that name is declared, only what it leaves unset is filled.
 */
final class QueryStringAugmenter implements PipeInterface
{
    /**
     * @param Specification $specification
     */
    public function __invoke(mixed $specification): mixed
    {
        $index = $specification->buildComponentIndex();

        foreach ($specification->operations as $operation) {
            foreach ($operation->attachables ?? [] as $queryString) {
                if ($queryString instanceof QueryString && null !== $model = $index->findSchema($queryString->modelRef)) {
                    $this->addParameters($operation, $model, $queryString);
                }
            }
        }

        return $specification;
    }

    private function addParameters(OAS\Operation $operation, OAS\Schema $model, QueryString $queryString): void
    {
        $optional = $queryString->argument->hasDefaultValue() || $queryString->argument->isNullable();

        foreach ($model->properties ?? [] as $property) {
            $schema = clone $property->schema;
            $name = 'array' === $schema->type ? $property->property.'[]' : $property->property;

            $parameter = $this->find($operation, $name);
            if (null === $parameter) {
                $operation->parameters = [...$operation->parameters ?? [], $parameter = new OAS\Parameter(name: $name, in: 'query')];
            }
            $parameter->schema ??= $schema;
            if (Undefined::isDefault($parameter->description) && !Undefined::isDefault($schema->description)) {
                $parameter->description = $schema->description;
            }
            $parameter->deprecated ??= $schema->deprecated;
            if (Undefined::isDefault($parameter->example) && !Undefined::isDefault($schema->example)) {
                $parameter->example = $schema->example;
            }
            $parameter->required ??= !$optional && \in_array($property->property, $model->required ?? [], true);
        }
    }

    private function find(OAS\Operation $operation, string $name): ?OAS\Parameter
    {
        foreach ($operation->parameters ?? [] as $parameter) {
            if ($parameter instanceof OAS\Parameter && $name === $parameter->name && 'query' === $parameter->in) {
                return $parameter;
            }
        }

        return null;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Reduce;
    }
}
