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
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithPromotedPropertiesWithDefaults;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

class PromotedPropertiesController81
{
    #[Route('/entity-with-promoted-properties-with-defaults', methods: ['GET'])]
    #[OAS\Operation\Get(
        operationId: 'getEntityWithPromotedPropertiesWithDefaults',
    )]
    #[OAS\Response(
        response: 204,
        description: 'Operation automatically detected',
    )]
    #[OAS\RequestBody(
        content: new Model(type: EntityWithPromotedPropertiesWithDefaults::class),
    )]
    public function entityWithPromotedPropertiesWithDefaults(): void
    {
    }
}
