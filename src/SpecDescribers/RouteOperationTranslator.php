<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use OpenApi\Contracts\AttributeTranslatorInterface;
use OpenApi\Spec as OAS;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Makes the operation of every route that targets a method exist during assembly, so the
 * attributes on that method nest into it as they would into an explicit #[Get].
 *
 * Replaces nelmio's OpenApiPhpDescriber. Reads the route table, not #[Route], so routes from
 * YAML or PHP config get the same treatment as attribute routes.
 */
final class RouteOperationTranslator implements AttributeTranslatorInterface
{
    /** The concrete controller class of the route; the reflector only names the declaring one. */
    public const META_CONTROLLER = 'nelmio.controller';
    private const META_EXPLICIT = 'nelmio.explicit';

    /** @var array<string, list<array{string, Route, class-string}>>|null "Class::method" => [[name, route, controller], ...] */
    private ?array $byMethod = null;

    public function __construct(
        private readonly RouteCollection $routes,
        private readonly ControllerReflector $controllerReflector,
    ) {
    }

    public function reset(): void
    {
    }

    public function getAttributes(\ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
    {
        return [];
    }

    public function translate(array $attributes, array $created, \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter|\ReflectionClassConstant $reflector): array
    {
        // an invokable controller carries its attributes on the class (nelmio semantics)
        if ($reflector instanceof \ReflectionClass) {
            $routes = $reflector->hasMethod('__invoke') ? $this->routesFor($reflector->getMethod('__invoke'), $reflector->getName()) : [];
        } elseif ($reflector instanceof \ReflectionMethod && '__invoke' !== $reflector->name) {
            $routes = $this->routesFor($reflector);
        } else {
            return $attributes;
        }

        // nelmio semantics: a method no route targets is not documented, explicit operations included
        if ([] === $routes && $reflector instanceof \ReflectionMethod) {
            return array_values(array_filter($attributes, static fn ($a) => !$a instanceof OAS\Operation));
        }
        if ([] === $routes) {
            return $attributes;
        }

        $explicit = array_values(array_filter($attributes, static fn ($a) => $a instanceof OAS\Operation));
        $operations = [];

        foreach ($routes as [$name, $route, $controller]) {
            $path = $this->normalizePath($route->getPath());
            foreach ($this->methods($route) as $method) {
                $operation = $this->claim($explicit, $method, $path);
                if (null === $operation) {
                    $attributes[] = $operation = new OAS\Operation(method: $method);
                } else {
                    $operation->setMeta(self::META_EXPLICIT, true);
                }
                $operation->path = $path;
                $operation->operationId ??= $method.'_'.$name;
                $operation->setMeta(self::META_CONTROLLER, $controller);
                $operations[] = $operation;
            }
        }

        // nelmio semantics: an explicit operation whose path is not the route's is ignored
        $attributes = array_values(array_filter($attributes, static fn ($a) => !\in_array($a, $explicit, true)));

        // classic nests a method's loose attributes into its explicit operation, if it has one;
        // only without one do they belong to every operation of the route
        $claimed = array_values(array_filter($operations, fn ($o) => $this->wasExplicit($o)));

        return $this->fanOut($attributes, [] !== $claimed ? $claimed : $operations);
    }

    private function wasExplicit(OAS\Operation $operation): bool
    {
        return true === $operation->getMeta(self::META_EXPLICIT);
    }

    /**
     * WORKAROUND (finding 13): one method, several operations (a route with several methods, or
     * none, meaning all). The assembler cannot tell which operation a sibling Response belongs to
     * and throws "Ambiguous merge", so the translator nests a copy into each itself. Shallow
     * clones: nested attributes are shared between the copies (PR 99).
     *
     * @param list<object>        $attributes
     * @param list<OAS\Operation> $operations
     *
     * @return list<object>
     */
    private function fanOut(array $attributes, array $operations): array
    {
        if (\count($operations) < 2 && 1 === \count(array_filter($attributes, static fn ($a) => $a instanceof OAS\Operation))) {
            return $attributes;
        }

        $kept = [];
        foreach ($attributes as $attribute) {
            $slot = match (true) {
                $attribute instanceof OAS\Response => 'responses',
                $attribute instanceof OAS\Parameter => 'parameters',
                $attribute instanceof OAS\RequestBody => 'requestBody',
                default => null,
            };
            if (null === $slot) {
                $kept[] = $attribute;
                continue;
            }
            foreach ($operations as $operation) {
                $copy = clone $attribute;
                if ('requestBody' === $slot) {
                    $operation->requestBody ??= $copy;
                } else {
                    $operation->{$slot} = [...$operation->{$slot} ?? [], $copy];
                }
            }
        }

        return $kept;
    }

    /**
     * @param list<OAS\Operation> $explicit
     */
    private function claim(array &$explicit, string $method, string $path): ?OAS\Operation
    {
        foreach ($explicit as $i => $operation) {
            // no method: it decorates the route's operation, whichever method (nelmio's #[Operation])
            if (\in_array($operation->method, [null, $method], true) && \in_array($operation->path, [null, $path], true)) {
                unset($explicit[$i]);
                $operation->method = $method;

                return $operation;
            }
        }

        return null;
    }

    /**
     * Routes are keyed by the declaring class of their target, which is what a reflector names.
     * With $controller set, only the routes of that concrete controller.
     *
     * @return list<array{string, Route, class-string}>
     */
    private function routesFor(\ReflectionMethod $method, ?string $controller = null): array
    {
        if (null === $this->byMethod) {
            $this->byMethod = [];
            foreach ($this->routes->all() as $name => $route) {
                if (!$route->hasDefault('_controller')) {
                    continue;
                }
                $target = $this->controllerReflector->getReflectionMethod($route->getDefault('_controller'));
                if (null !== $target) {
                    $concrete = explode('::', $route->getDefault('_controller'))[0];
                    $this->byMethod[$target->class.'::'.$target->name][] = [$name, $route, $concrete];
                }
            }
        }

        $routes = $this->byMethod[$method->class.'::'.$method->name] ?? [];

        return null === $controller ? $routes : array_values(array_filter($routes, static fn ($r) => $r[2] === $controller));
    }

    /**
     * @return list<string>
     */
    private function methods(Route $route): array
    {
        $methods = array_map('strtolower', $route->getMethods() ?: Util::OPERATIONS);

        return array_values(array_intersect($methods, Util::OPERATIONS));
    }

    private function normalizePath(string $path): string
    {
        return str_ends_with($path, '.{_format}') ? substr($path, 0, -10) : $path;
    }
}
