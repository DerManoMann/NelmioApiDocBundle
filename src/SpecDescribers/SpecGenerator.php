<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Augmenter;
use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Builder\Result;
use OpenApi\Merge;
use OpenApi\Utils\AttributeFactory;
use OpenApi\Utils\Pipeline;
use OpenApi\Utils\TypedList;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The PoC's replacement for ApiDocGenerator: nelmio's describers, swagger-php's spec pipeline.
 */
final class SpecGenerator
{
    public function __construct(
        private readonly NelmioServices $services,
        private readonly string $version = '3.0.0',
        // finding 13: Builder::collectSafely() reports to this logger, not to Result::log()
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function build(): Result
    {
        return $this->builder()->build();
    }

    public function builder(): Builder
    {
        $routes = $this->services->routes();
        $reflector = $this->services->controllerReflector();

        // one scratch document and one registry for every describer (review risk 2)
        $scratch = new OA\OpenApi(['_context' => Util::createContext(['version' => $this->version])]);
        $registry = new ModelRegistry($this->services->modelDescribers(), $scratch, $this->services->alternativeNames());

        $builder = (new Builder())
            ->setMode(Mode::SPEC)
            ->setLogger($this->logger)
            ->setVersion($this->version)
            ->withAttributeFactory(fn (AttributeFactory $factory) => $factory->withTranslators(
                fn (TypedList $translators) => $translators
                    ->add(new RouteOperationTranslator($routes, $reflector))
                    ->add(new SecurityTranslator())
                    ->add(new NelmioOperationTranslator())
            ))
            ->withMergers(fn (TypedList $mergers) => $mergers->insert(new DeepOperations(), Merge\LastWins::class))
            ->withAugmenters(fn (Pipeline $augmenters) => $augmenters
                ->add(new ClassTagAugmenter())
                ->add(new DefaultsAugmenter())
                ->insert(new NullableAugmenter(), Augmenter\Cleanup::class)
                ->insert(new RequestPayloadAugmenter(), Augmenter\Cleanup::class)
                ->insert(new QueryStringAugmenter(), Augmenter\Cleanup::class))
            // nelmio keeps unreferenced components unless CleanUnusedComponents is registered
            ->withAugmenters(fn (Pipeline $augmenters) => $augmenters->get(Augmenter\Cleanup::class)?->setEnabled($this->services->cleanup()))
            // nelmio's PhpDocDescriber reads docblocks its own way (methods only)
            ->withAugmenters(fn (Pipeline $augmenters) => $augmenters->remove(Augmenter\Docblocks::class))
            // nelmio removes classic's OperationId processor, and classic gives an implicit tag no description
            ->withAugmenters(fn (Pipeline $augmenters) => $augmenters->remove(Augmenter\OperationIds::class))
            ->withAugmenters(fn (Pipeline $augmenters) => $augmenters->get(Augmenter\Tags::class)?->setWithDescription(false))
            ->withSpecification(new DescriberContribution($this->services->describers(), $registry, $scratch))
            ->withSpecification(new ModelContribution($registry, $scratch, $this->services->mediaTypes()));

        // WORKAROUND (finding 1): a ReflectionMethod source is dropped, so seed classes: the route's
        // concrete controller (class-level attributes) and the method's declaring class (method attributes)
        $classes = [];
        foreach ($routes as $route) {
            if ($route->hasDefault('_controller') && null !== $method = $reflector->getReflectionMethod($route->getDefault('_controller'))) {
                $concrete = explode('::', $route->getDefault('_controller'))[0];
                $classes[$concrete] ??= new \ReflectionClass($concrete);
                $classes[$method->class] ??= new \ReflectionClass($method->class);
            }
        }
        $builder->addSource(array_values($classes));

        return $builder;
    }
}
