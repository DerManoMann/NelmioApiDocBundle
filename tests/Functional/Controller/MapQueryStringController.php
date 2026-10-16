<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional\Controller;

use Nelmio\ApiDocBundle\Tests\Functional\Entity\QueryModel\ArrayQueryModel;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\QueryModel\FilterQueryModel;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\QueryModel\PaginationQueryModel;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\QueryModel\SortQueryModel;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyConstraintsWithValidationGroups;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyMapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Components]
class MapQueryStringController
{
    #[Route('/article_map_query_string', methods: ['GET'])]
    #[OAS\Response(response: '200', description: '')]
    public function fetchArticleFromMapQueryString(
        #[MapQueryString]
        SymfonyMapQueryString $article81Query,
    ): void {
    }

    #[Route('/article_map_query_string_nullable', methods: ['GET'])]
    #[OAS\Response(response: '200', description: '')]
    public function fetchArticleFromMapQueryStringNullable(
        #[MapQueryString]
        ?SymfonyMapQueryString $article81Query,
    ): void {
    }

    #[Route('/article_map_query_string_passes_validation_groups', methods: ['GET'])]
    #[OAS\Response(response: '200', description: '')]
    public function fetchArticleFromMapQueryStringHandlesValidationGroups(
        #[MapQueryString(validationGroups: ['test'])]
        SymfonyConstraintsWithValidationGroups $symfonyConstraintsWithValidationGroups,
    ): void {
    }

    #[Route('/article_map_query_string_overwrite_parameters', methods: ['GET'])]
    #[OAS\Parameter(
        name: 'id',
        in: 'query',
        schema: new OAS\Schema(type: 'string', nullable: true),
        description: 'Query parameter id description'
    )]
    #[OAS\Parameter(
        name: 'name',
        in: 'query',
        description: 'Query parameter name description'
    )]
    #[OAS\Parameter(
        name: 'nullableName',
        in: 'query',
        description: 'Query parameter nullableName description'
    )]
    #[OAS\Parameter(
        name: 'articleType81',
        in: 'query',
        description: 'Query parameter articleType81 description'
    )]
    #[OAS\Parameter(
        name: 'nullableArticleType81',
        in: 'query',
        description: 'Query parameter nullableArticleType81 description'
    )]
    #[OAS\Response(response: '200', description: '')]
    public function fetchArticleFromMapQueryStringOverwriteParameters(
        #[MapQueryString]
        SymfonyMapQueryString $article81Query,
    ): void {
    }

    #[Route('/article_map_query_string_many_parameters', methods: ['GET'])]
    #[OAS\Response(response: '200', description: '')]
    public function fetchArticleWithManyParameters(
        #[MapQueryString]
        FilterQueryModel $filterQuery,
        #[MapQueryString]
        PaginationQueryModel $paginationQuery,
        #[MapQueryString]
        SortQueryModel $sortQuery,
        #[MapQueryString]
        ArrayQueryModel $arrayQuery,
    ): void {
    }

    #[Route('/article_map_query_string_many_parameters_optional')]
    #[OAS\Response(response: '200', description: '')]
    public function fetchArticleWithManyOptionalParameters(
        #[MapQueryString]
        ?FilterQueryModel $filterQuery,
        #[MapQueryString]
        ?PaginationQueryModel $paginationQuery,
        #[MapQueryString]
        ?SortQueryModel $sortQuery,
        #[MapQueryString]
        ?ArrayQueryModel $arrayQuery,
    ): void {
    }
}
