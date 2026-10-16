<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\RouteDescriber\RouteArgumentDescriber\SymfonyMapQueryStringDescriber;
use Nelmio\ApiDocBundle\RouteDescriber\RouteArgumentDescriber\SymfonyMapRequestPayloadDescriber;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use OpenApi\Specification;

/**
 * Runs nelmio's describers (routes, security, external docs) into one scratch classic document,
 * converts it and contributes the result. Operations go in as partials: Merge\Operations folds each
 * into the operation RouteOperationTranslator made for the same path and method.
 */
final class DescriberContribution
{
    public const META = 'nelmio.describer';

    /**
     * @param iterable<DescriberInterface> $describers
     */
    public function __construct(
        private readonly iterable $describers,
        private readonly ModelRegistry $modelRegistry,
        private readonly OA\OpenApi $scratch,
        private readonly DocumentBridge $bridge = new DocumentBridge(),
    ) {
    }

    /**
     * WORKAROUND (finding 11): the bridge drops _context extras; nelmio's argument describers use
     * them as a side channel to their processors.
     */
    private function carryPayload(?OA\Operation $classic, \OpenApi\Spec\Operation $operation): void
    {
        $context = $classic?->_context;
        $argument = $context?->{SymfonyMapRequestPayloadDescriber::CONTEXT_ARGUMENT_METADATA} ?? null;
        if ($argument instanceof ArgumentMetadata) {
            $operation->attachables = [...$operation->attachables ?? [], new RequestPayload($argument, $context->{SymfonyMapRequestPayloadDescriber::CONTEXT_MODEL_REF})];
        }
        foreach ($context?->{SymfonyMapQueryStringDescriber::CONTEXT_KEY} ?? [] as $queryString) {
            $operation->attachables = [...$operation->attachables ?? [], new QueryString(
                $queryString[SymfonyMapQueryStringDescriber::CONTEXT_ARGUMENT_METADATA],
                $queryString[SymfonyMapQueryStringDescriber::CONTEXT_MODEL_REF],
            )];
        }
    }

    private function scannedReflector(Specification $specification, \OpenApi\Spec\Operation $operation): ?\Reflector
    {
        foreach ($specification->operations as $scanned) {
            if ($scanned->path === $operation->path && $scanned->method === $operation->method) {
                return $scanned->getReflector();
            }
        }

        return null;
    }

    private function classicOperation(string $path, string $method): ?OA\Operation
    {
        foreach ($this->scratch->paths as $pathItem) {
            if ($pathItem->path === $path && $pathItem->{$method} instanceof OA\Operation) {
                return $pathItem->{$method};
            }
        }

        return null;
    }

    public function __invoke(Specification $specification): void
    {
        foreach ($this->describers as $describer) {
            if ($describer instanceof ModelRegistryAwareInterface) {
                $describer->setModelRegistry($this->modelRegistry);
            }
            $describer->describe($this->scratch);
        }

        $described = $this->bridge->fromDocument($this->scratch);
        foreach ($described->operations as $operation) {
            $this->carryPayload($this->classicOperation($operation->path, $operation->method), $operation);
            // the route's method, taken from the operation the translator made for it
            $operation->setReflector($this->scannedReflector($specification, $operation));
            $specification->add($operation->setMeta(self::META, true));
        }
        // everything but schemas: those come from the model registry, in ModelContribution
        $specification->add(
            ...$described->securitySchemes, ...$described->tags, ...$described->servers,
            ...$described->parameters, ...$described->responses, ...$described->requestBodies,
            ...$described->headers, ...$described->examples, ...$described->links,
            ...$described->externalDocs,
        );
        // document-level fields from nelmio's `documentation` config (security, x)
        $specification->openapi->security ??= $described->openapi?->security;
        $specification->openapi->x = [...$specification->openapi->x ?? [], ...$described->openapi?->x ?? []] ?: null;
        if (null !== $described->info) {
            $specification->add($described->info);
        }
    }
}
