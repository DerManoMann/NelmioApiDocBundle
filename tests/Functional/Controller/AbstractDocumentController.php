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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Components]
abstract class AbstractDocumentController
{
    #[Route('', methods: ['GET'])]
    #[OAS\Response(response: 200, description: 'List documents')]
    public function getDocuments(): JsonResponse
    {
    }
}
