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

use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\ArticleHolder;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithAlternateType;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithRef;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

/**
 * SPIKE: models with classic attributes; {@see Spec\ModelProbeController} is its twin.
 */
class ModelProbeController
{
    #[Route('/probe/models', name: 'probe_models', methods: 'GET')]
    #[OA\Response(response: 200, description: 'User', content: new Model(type: User::class))]
    #[OA\Response(response: 201, description: 'Alternate type', content: new Model(type: EntityWithAlternateType::class))]
    #[OA\Response(response: 202, description: 'Ref', content: new Model(type: EntityWithRef::class))]
    #[OA\Response(response: 203, description: 'Holder', content: new Model(type: ArticleHolder::class, groups: ['light']))]
    public function models(): void
    {
    }
}
