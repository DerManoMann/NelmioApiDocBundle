<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Describer;

use Nelmio\ApiDocBundle\Attribute\Spec\Model as SpecModel;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use Nelmio\ApiDocBundle\Util\LegacyTypeConverter;
use OpenApi\Annotations as OA;
use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Assembler\OperationMethodsTranslator;
use OpenApi\Augmenter\DefaultMediaType;
use OpenApi\Augmenter\OperationTags;
use OpenApi\Augmenter\Shortcuts;
use OpenApi\Compiler\OpenApi30Compiler;
use OpenApi\Compiler\OpenApi31Compiler;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Utils\AttributeFactory;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Reads swagger-php spec attributes (`OpenApi\Spec`) on route methods, next to the classic ones.
 *
 * PROTOTYPE: one route at a time, assembled and compiled by swagger-php, then merged into the
 * classic operation as an array.
 */
final class SpecAttributeDescriber implements DescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;
    use RouteDescriberTrait;

    /**
     * @param string[] $mediaTypes
     */
    public function __construct(
        private readonly RouteCollection $routeCollection,
        private readonly ControllerReflector $controllerReflector,
        private readonly array $mediaTypes = ['json'],
    ) {
    }

    public function describe(OA\OpenApi $api): void
    {
        foreach ($this->routeCollection->all() as $route) {
            $method = $this->controllerReflector->getReflectionMethod($route->getDefault('_controller'));
            if (null === $method) {
                continue;
            }

            $factory = new AttributeFactory();
            $class = $method->getDeclaringClass();
            if (!$factory->hasAttributes($method) && !$factory->hasAttributes($class)) {
                continue;
            }

            $path = $this->normalizePath($route->getPath());
            $httpMethods = $this->getSupportedHttpMethods($route);

            // the route defines the operation; the attributes on the method decorate it
            $factory->getTranslators()->insert(new class($path, $httpMethods) extends AbstractAttributeTranslator {
                /** @param string[] $methods */
                public function __construct(private readonly string $path, private readonly array $methods)
                {
                }

                public function translate(array $attributes, array $created, \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
                {
                    $attributes = [...$attributes, ...$created];
                    if (!$reflector instanceof \ReflectionMethod) {
                        return $attributes;
                    }

                    $explicit = array_filter($attributes, static fn ($a) => $a instanceof OAS\Operation);
                    // an explicit operation describes the route it sits on, as with the classic attributes
                    foreach ($explicit as $operation) {
                        $operation->path ??= $this->path;
                    }
                    if ([] === $explicit) {
                        $attributes[] = (new OAS\Operation(path: $this->path, methods: $this->methods))->setReflector($reflector);
                    }

                    return $attributes;
                }
            }, OperationMethodsTranslator::class);

            // PROTOTYPE: of the class-level attributes only tags, which nelmio gives to every operation in the class
            $classTags = array_filter((new AttributeFactory())->fromReflector($class), static fn ($a) => $a instanceof OAS\Tag);
            $specification = (new Specification())->add(...$factory->fromReflector($method), ...$classTags);
            // the augmenters that only reshape what the attributes say; the rest is nelmio's
            (new Shortcuts())($specification);
            (new DefaultMediaType($this->defaultMediaType()))($specification);
            (new OperationTags())($specification);

            $specification->getWalker()->visit(SpecModel::class, function (SpecModel $model): void {
                $definition = $model->getModel();
                $model->ref = $this->modelRegistry->register(new Model(
                    LegacyTypeConverter::createType($definition['type']),
                    $definition['groups'],
                    $definition['options'],
                    $definition['serializationContext'],
                    $definition['name'],
                ));
            });

            $compiler = str_starts_with($api->openapi, '3.0') ? new OpenApi30Compiler() : new OpenApi31Compiler();
            $compiled = $compiler->compile($specification);

            foreach ($compiled['paths'][$path] ?? [] as $httpMethod => $operation) {
                Util::merge(Util::getOperation(Util::getPath($api, $path), $httpMethod), $operation);
            }
            foreach ($compiled['tags'] ?? [] as $tag) {
                Util::merge(Util::getTag($api, $tag['name']), $tag);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getSupportedHttpMethods(Route $route): array
    {
        $methods = array_map('strtolower', $route->getMethods());

        return [] === $methods ? Util::OPERATIONS : array_values(array_intersect($methods, Util::OPERATIONS));
    }

    private function defaultMediaType(): string
    {
        return match ($this->mediaTypes[0] ?? 'json') {
            'json' => 'application/json',
            'xml' => 'application/xml',
            default => $this->mediaTypes[0],
        };
    }
}
