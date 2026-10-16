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

use Nelmio\ApiDocBundle\SpecDescribers\Model;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article81;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithNullableSchemaSet;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyConstraintsWithValidationGroups;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Components]
class MapRequestPayloadController
{
    #[Route('/article_map_request_payload', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayload(
        #[MapRequestPayload]
        Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_nullable', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadNullable(
        #[MapRequestPayload]
        ?Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_overwrite', methods: ['POST'])]
    #[OAS\RequestBody(
        description: 'Request body description',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithNullableSchemaSet::class)),
    )]
    #[OAS\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadOverwrite(
        #[MapRequestPayload]
        Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_handles_already_set_content', methods: ['POST'])]
    #[OAS\RequestBody(
        description: 'Request body description',
        content: new OAS\MediaType\Json(
            ref: new Model(type: Article81::class)
        ),
    )]
    #[OAS\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadHandlesAlreadySetContent(
        #[MapRequestPayload]
        Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_validation_groups', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadPassedValidationGroups(
        #[MapRequestPayload(validationGroups: ['test'])]
        SymfonyConstraintsWithValidationGroups $symfonyConstraintsWithValidationGroups,
    ): void {
    }
}
