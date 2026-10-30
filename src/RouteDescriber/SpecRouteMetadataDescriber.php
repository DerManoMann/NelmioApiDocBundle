<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\RouteDescriber;

use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Compiler\OpenApi30Compiler;
use OpenApi\Compiler\OpenApi31Compiler;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Undefined;
use Symfony\Component\Routing\Route;

/**
 * SPIKE: {@see RouteMetadataDescriber} building spec attributes. What it describes is compiled
 * and merged into the classic operation without overwriting, so it only fills what is missing,
 * as the classic one does by checking each field.
 */
final class SpecRouteMetadataDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    private const ALPHANUM_EXPANDED_REGEX = '/^[-a-zA-Z0-9_]*$/';
    private const PATH = '/__nelmio_fragment';

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $requirements = $route->getRequirements();
        $pathVariables = array_values(array_filter($route->compile()->getPathVariables(), static fn (string $variable): bool => '_format' !== $variable));

        foreach ($this->getOperations($api, $route) as $operation) {
            $existingParams = $this->getRefParams($api, $operation);

            $parameters = [];
            foreach ($pathVariables as $pathVariable) {
                $parameter = $existingParams[$pathVariable.'/path'] ?? null;
                if (null !== $parameter) {
                    if (!$parameter->required || Undefined::isDefault($parameter->required)) {
                        throw new \LogicException(\sprintf('Global parameter "%s" is used as part of route "%s" and must be set as "required"', $pathVariable, $route->getPath()));
                    }

                    continue;
                }

                $requirement = $requirements[$pathVariable] ?? null;
                $enumValues = null !== $requirement ? $this->getPossibleEnumValues($requirement) : [];

                // the enum only goes with the requirement's pattern, not with one declared on the parameter
                $existing = $this->findParameter($operation, $pathVariable);
                $hasPattern = null !== $existing && !Undefined::isDefault($existing->schema) && !Undefined::isDefault($existing->schema->pattern);

                $parameters[] = new OAS\Parameter\Path(
                    name: $pathVariable,
                    schema: new OAS\Schema(
                        type: 'string',
                        pattern: $requirement,
                        enum: [] !== $enumValues && !$hasPattern ? $enumValues : null,
                    ),
                );
            }

            if ([] === $parameters) {
                continue;
            }

            $specification = (new Specification())->add(new OAS\Operation(path: self::PATH, method: 'get', parameters: $parameters));
            $compiler = str_starts_with($api->openapi, '3.0') ? new OpenApi30Compiler() : new OpenApi31Compiler();

            foreach ($compiler->compile($specification)['paths'][self::PATH]['get']['parameters'] ?? [] as $compiled) {
                Util::merge(Util::getOperationParameter($operation, $compiled['name'], 'path'), $compiled);
            }
        }
    }

    private function findParameter(OA\Operation $operation, string $name): ?OA\Parameter
    {
        foreach (!Undefined::isDefault($operation->parameters) ? $operation->parameters : [] as $parameter) {
            if ($parameter->name === $name && 'path' === $parameter->in) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @return OA\Parameter[] existing $ref parameters
     */
    private function getRefParams(OA\OpenApi $api, OA\Operation $operation): array
    {
        /** @var OA\Parameter[] $globalParams */
        $globalParams = !Undefined::isDefault($api->components) && !Undefined::isDefault($api->components->parameters) ? $api->components->parameters : [];
        $globalParams = array_column($globalParams, null, 'parameter');
        $existingParams = [];

        foreach (!Undefined::isDefault($operation->parameters) ? $operation->parameters : [] as $parameter) {
            if (Undefined::isDefault($parameter->ref)) {
                continue;
            }

            $ref = mb_substr($parameter->ref, 24);
            if (!isset($globalParams[$ref])) {
                continue;
            }

            $refParameter = $globalParams[$ref];
            $existingParams[\sprintf('%s/%s', $refParameter->name, $refParameter->in)] = $refParameter;
        }

        return $existingParams;
    }

    /**
     * @return array<string>
     */
    private function getPossibleEnumValues(string $reqPattern): array
    {
        $requirements = [];
        if (str_contains($reqPattern, '|')) {
            foreach (explode('|', $reqPattern) as $part) {
                if ('' === $part || 0 === preg_match(self::ALPHANUM_EXPANDED_REGEX, $part)) {
                    return [];
                }
                $requirements[] = $part;
            }
        }

        return $requirements;
    }
}
