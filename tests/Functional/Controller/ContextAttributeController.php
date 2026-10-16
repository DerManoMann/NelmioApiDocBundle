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
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithClassLevelContext;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithContext;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithGroupScopedContext;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[OAS\Components]
class ContextAttributeController
{
    #[OAS\Response(
        response: '200',
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithContext::class)),
    )]
    #[Route('/context-attribute', methods: ['GET'])]
    public function contextAction(): void
    {
    }

    #[OAS\Response(
        response: '200',
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithClassLevelContext::class)),
    )]
    #[Route('/context-attribute-class-level', methods: ['GET'])]
    public function classLevelContextAction(): void
    {
    }

    #[OAS\Response(
        response: '200',
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithGroupScopedContext::class, groups: ['plain'])),
    )]
    #[Route('/context-attribute-group-plain', methods: ['GET'])]
    public function groupScopedContextInactiveAction(): void
    {
    }

    #[OAS\Response(
        response: '200',
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithGroupScopedContext::class, groups: ['forced'])),
    )]
    #[Route('/context-attribute-group-forced', methods: ['GET'])]
    public function groupScopedContextActiveAction(): void
    {
    }

    #[OAS\Response(
        response: '200',
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: EntityWithGroupScopedContext::class, groups: ['plain', 'type' => ['forced']])),
    )]
    #[Route('/context-attribute-group-nested', methods: ['GET'])]
    public function groupScopedContextNestedAction(): void
    {
    }
}
