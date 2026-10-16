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

use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Tag(name: 'My tag name', description: 'My description of the tag', externalDocs: new OAS\ExternalDocumentation(url: 'https://example.com'))]
#[OAS\Components]
class OpenApiTagController
{
    #[Route('/some_post', methods: ['POST'])]
    #[OAS\Response(response: '200', description: '')]
    public function somePost(): void
    {
    }

    #[Route('/some_get', methods: ['GET'])]
    #[OAS\Response(response: '200', description: '')]
    public function someGet(): void
    {
    }
}
