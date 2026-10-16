<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Annotations as OA;
use OpenApi\HybridBridge;
use OpenApi\Specification;
use OpenApi\Spec;

/**
 * Converts a classic document *tree* into a Specification.
 *
 * WORKAROUND (finding 2): HybridBridge::fromAnalysis() converts a scan - flat root annotations,
 * each with a reflector. nelmio's describers build a tree under one OA\OpenApi with no reflectors,
 * so this walks the tree and calls the bridge's protected converters directly.
 */
final class DocumentBridge extends HybridBridge
{
    public function fromDocument(OA\OpenApi $openApi, Specification $specification = new Specification()): Specification
    {
        $this->convertOpenApiMeta($openApi, $specification);

        if ($openApi->info instanceof OA\Info) {
            $specification->info = $this->convertInfo($openApi->info);
        }
        foreach ($this->each($openApi->servers) as $server) {
            $specification->servers[] = $this->convertServer($server);
        }
        foreach ($this->each($openApi->tags) as $tag) {
            $specification->tags[] = $this->convertTag($tag);
        }
        foreach ($this->each($openApi->paths) as $pathItem) {
            $this->collectPathItem($pathItem, $specification);
        }
        if ($openApi->components instanceof OA\Components) {
            $this->convertComponents($openApi->components, $specification);
        }

        return $specification;
    }

    /**
     * Converts one classic operation; the path and method come from the caller.
     */
    public function operation(OA\Operation $operation, string $path, string $method): Spec\Operation
    {
        return $this->convertOperation($operation, $path, $method);
    }

    public function schema(OA\Schema $schema): Spec\Schema
    {
        return $this->convertSchema($schema);
    }

    private function each(mixed $value): array
    {
        return \is_array($value) ? $value : [];
    }
}
