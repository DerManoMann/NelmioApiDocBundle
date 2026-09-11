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

use Nelmio\ApiDocBundle\SpecPoC\Attributes\ContentModel;
use Nelmio\ApiDocBundle\SpecPoC\Attributes\RefModel;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article81;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithNullableSchemaSet;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyConstraintsWithValidationGroups;
use OpenApi\Spec as OA;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class MapRequestPayloadController
{
    #[Route('/article_map_request_payload', methods: ['POST'])]
    #[OA\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayload(
        #[MapRequestPayload]
        Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_nullable', methods: ['POST'])]
    #[OA\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadNullable(
        #[MapRequestPayload]
        ?Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_overwrite', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Request body description',
        content: new ContentModel(EntityWithNullableSchemaSet::class),
    )]
    #[OA\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadOverwrite(
        #[MapRequestPayload]
        Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_handles_already_set_content', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Request body description',
        content: new OA\MediaType\Json(
            ref: new RefModel(Article81::class)
        ),
    )]
    #[OA\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadHandlesAlreadySetContent(
        #[MapRequestPayload]
        Article81 $article81,
    ): void {
    }

    #[Route('/article_map_request_payload_validation_groups', methods: ['POST'])]
    #[OA\Response(response: '200', description: '')]
    public function createArticleFromMapRequestPayloadPassedValidationGroups(
        #[MapRequestPayload(validationGroups: ['test'])]
        SymfonyConstraintsWithValidationGroups $symfonyConstraintsWithValidationGroups,
    ): void {
    }
}
