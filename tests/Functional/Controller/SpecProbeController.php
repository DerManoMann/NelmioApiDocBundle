<?php

namespace Nelmio\ApiDocBundle\Tests\Functional\Controller;

use Nelmio\ApiDocBundle\Attribute\Spec\Model;
use OpenApi\Spec as OA;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article;
use Symfony\Component\Routing\Attribute\Route;

// PROTOTYPE: a classic controller and its spec twin; the classic one writes the fixture
#[Route('/probe')]
final class SpecProbeController
{
    #[Route('/articles/{id}', name: 'probe_get', methods: 'GET')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The article id', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'expand', in: 'query', description: 'Expand relations', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(response: 200, description: 'The article', content: new OA\MediaType\Json(properties: [new OA\Property(property: 'article', schema: new Model(type: Article::class)), new OA\Property(property: 'related', schema: new OA\Schema\Items(type: 'string'))]))]
    #[OA\Response(response: 404, description: 'Not found')]
    public function getArticle(int $id): void
    {
    }

    #[Route('/articles', name: 'probe_list', methods: 'GET')]
    #[OA\Operation\Get(operationId: 'listArticles', summary: 'List articles')]
    #[OA\Response(response: 200, description: 'All articles', content: new OA\MediaType\Json(type: 'array', items: new Model(type: Article::class)))]
    public function listArticles(): void
    {
    }
}
