<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Describer\DefaultDescriber;
use OpenApi\Processors\CleanUnusedComponents;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\Describer\OpenApiPhpDescriber;
use Nelmio\ApiDocBundle\Describer\RouteDescriber;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Nelmio\ApiDocBundle\Tests\Functional\ConfigurableContainerFactory;
use Nelmio\ApiDocBundle\Tests\Functional\ControllerTest;
use Nelmio\ApiDocBundle\Tests\Functional\TestKernel;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollection;

/**
 * SHORTCUT: nelmio's test kernel as a service locator, so the describers come configured as in
 * nelmio's own test run.
 * Private properties are read by reflection; DI is out of scope for the PoC.
 */
final class NelmioServices
{
    private object $generator;
    private bool $cleanup;
    private object $container;

    /**
     * The test case's own bundles, config and routes come from ControllerTest::provideTestCases(),
     * matched on controller and fixture suffix, so the container is configured as nelmio's run was.
     */
    /**
     * FunctionalTest's world: the full TestKernel (routes.yaml, areas, API Platform), default area.
     */
    public static function testKernel(): self
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $services = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $services->container = $kernel->getContainer()->get('test.service_container');
        $services->generator = $services->container->get('nelmio_api_doc.generator.default');
        $services->cleanup = false;

        return $services;
    }

    public function __construct(string $controller, ?string $suffix = null)
    {
        [$bundles, $configs, $definitions, $extraRoutes] = [[], [], [], null];
        foreach (ControllerTest::provideTestCases() as $case) {
            if (($case[0] ?? null) === $controller && ($case[1] ?? null) === $suffix) {
                [$bundles, $configs, $definitions, $extraRoutes] = [$case[2] ?? [], $case[3] ?? [], $case[4] ?? [], $case[5] ?? null];
                break;
            }
        }

        $this->cleanup = isset($definitions[CleanUnusedComponents::class]);

        $factory = new ConfigurableContainerFactory();
        $factory->create($bundles, static function (RoutingConfigurator &$routes) use ($controller, $extraRoutes) {
            if (null !== $extraRoutes) {
                $extraRoutes($routes);
            }
            $routes->withPath('/')->import(__DIR__."/../../tests/Functional/Controller/$controller.php", 'attribute');
        }, $configs, $definitions);
        $this->container = $factory->getContainer();
        $this->generator = $this->container->get('nelmio_api_doc.generator.default');
    }

    /**
     * Whether the case registers nelmio's CleanUnusedComponents processor.
     */
    public function cleanup(): bool
    {
        return $this->cleanup;
    }

    public function routes(): RouteCollection
    {
        // the area's routes, as nelmio's RouteDescriber sees them, not the router's full collection
        return $this->private($this->routeDescriber(), 'routeCollection');
    }

    public function controllerReflector(): ControllerReflector
    {
        return $this->private($this->routeDescriber(), 'controllerReflector');
    }

    public function routeDescriber(): RouteDescriber
    {
        foreach ($this->private($this->generator, 'describers') as $describer) {
            if ($describer instanceof RouteDescriber) {
                return $describer;
            }
        }

        throw new \LogicException('no route describer');
    }

    /**
     * Every describer except the two the PoC replaces: OpenApiPhpDescriber (the spec pipeline
     * reads attributes itself) and DefaultDescriber (DefaultsAugmenter, as it needs the merged result).
     *
     * @return list<DescriberInterface>
     */
    public function describers(): array
    {
        return array_values(array_filter(
            iterator_to_array($this->private($this->generator, 'describers'), false),
            static fn ($d) => !$d instanceof OpenApiPhpDescriber && !$d instanceof DefaultDescriber,
        ));
    }

    /**
     * @return iterable<ModelDescriberInterface>
     */
    /**
     * @return array<string, mixed> nelmio's `models.names`
     */
    public function alternativeNames(): array
    {
        return $this->private($this->generator, 'alternativeNames');
    }

    /**
     * @return list<string>
     */
    public function mediaTypes(): array
    {
        return $this->private($this->generator, 'mediaTypes');
    }

    public function modelDescribers(): iterable
    {
        return $this->private($this->generator, 'modelDescribers');
    }

    private function private(object $object, string $name): mixed
    {
        return (new \ReflectionProperty($object, $name))->getValue($object);
    }
}
