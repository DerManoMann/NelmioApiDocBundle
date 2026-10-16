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
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithSelfReferencingContext;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Components]
class SelfReferencingContextController
{
    #[OAS\Response(
        response: '200',
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithSelfReferencingContext::class)),
    )]
    #[Route('/self-referencing-context', methods: ['GET'])]
    public function selfReferencingContextAction(): void
    {
    }
}
