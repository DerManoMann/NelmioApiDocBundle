<?php

namespace Nelmio\ApiDocBundle\Tests\Functional\Controller;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article;
use Symfony\Component\Routing\Attribute\Route;

// PROTOTYPE: a classic controller and its spec twin; the classic one writes the fixture
#[Route('/probe')]
final class ClassicProbeController
{
    #[Route('/articles/{id}', name: 'probe_get', methods: 'GET')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The article id', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'expand', in: 'query', description: 'Expand relations', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(response: 200, description: 'The article', content: new OA\JsonContent(properties: [new OA\Property(property: 'article', ref: new Model(type: Article::class)), new OA\Property(property: 'related', type: 'array', items: new OA\Items(type: 'string'))]))]
    #[OA\Response(response: 404, description: 'Not found')]
    public function getArticle(int $id): void
    {
    }

    #[Route('/articles', name: 'probe_list', methods: 'GET')]
    #[OA\Get(operationId: 'listArticles', summary: 'List articles')]
    #[OA\Response(response: 200, description: 'All articles', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Article::class))))]
    public function listArticles(): void
    {
    }
}
