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

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Components]
class MapUploadedFileController
{
    #[Route('/article_map_uploaded_file', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createUploadFromMapUploadedFilePayload(
        #[MapUploadedFile]
        UploadedFile $upload,
    ): void {
    }

    #[Route('/article_map_uploaded_file_nullable', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createUploadFromMapUploadedFilePayloadNullable(
        #[MapUploadedFile]
        ?UploadedFile $upload,
    ): void {
    }

    #[Route('/article_map_uploaded_file_multiple', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createUploadFromMapUploadedFilePayloadMultiple(
        #[MapUploadedFile]
        UploadedFile $firstUpload,
        #[MapUploadedFile]
        UploadedFile $secondUpload,
    ): void {
    }

    #[Route('/article_map_uploaded_file_add_to_existing', methods: ['POST'])]
    #[OAS\RequestBody(content: [
        new OAS\MediaType(mediaType: 'multipart/form-data', schema: new OAS\Schema(
            properties: [new OAS\Property(property: 'existing', schema: new OAS\Schema(type: 'string', format: 'binary'))],
            type: 'object',
        )),
    ])]
    #[OAS\Response(response: '200', description: '')]
    public function createUploadFromMapUploadedFileAddToExisting(
        #[MapUploadedFile]
        ?UploadedFile $upload,
    ): void {
    }

    #[Route('/article_map_uploaded_file_overwrite', methods: ['POST'])]
    #[OAS\RequestBody(
        description: 'Body if file upload request',
        content: [
            new OAS\MediaType(mediaType: 'multipart/form-data', schema: new OAS\Schema(
                properties: [new OAS\Property(
                    property: 'upload',
                    schema: new OAS\Schema(description: 'A file'),
                )],
                type: 'object',
            )),
        ],
    )]
    #[OAS\Response(response: '200', description: '')]
    public function createUploadFromMapUploadedFileOverwrite(
        #[MapUploadedFile]
        ?UploadedFile $upload,
    ): void {
    }

    #[Route('/article_map_uploaded_file_array', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createArrayUploadFromMapUploadedFilePayload(
        #[MapUploadedFile]
        array $upload,
    ): void {
    }

    #[Route('/article_map_uploaded_file_variadic', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function createVariadicUploadFromMapUploadedFilePayload(
        #[MapUploadedFile]
        UploadedFile ...$upload,
    ): void {
    }
}
