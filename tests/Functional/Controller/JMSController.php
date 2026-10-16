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
use Nelmio\ApiDocBundle\Tests\Functional\Entity\DiscriminatorMap\JMSAbstractUser;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSComplex;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSDualComplex;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSEnum;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSIgnoredProperty;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSNamingStrategyConstraints;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSUser;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSChat;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSChatRoomUser;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSChatUser;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSPicture;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\VirtualProperty;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[Route(host: 'api.example.com')]
#[OAS\Components]
class JMSController
{
    #[Route('/api/jms', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSUser::class))
    )]
    public function userAction(): void
    {
    }

    #[Route('/api/yaml', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: VirtualProperty::class))
    )]
    public function yamlAction(): void
    {
    }

    #[Route('/api/jms_complex', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(
            type: JMSComplex::class,
            groups: ['list', 'details', 'User' => ['list']]
        ))
    )]
    public function complexAction(): void
    {
    }

    #[Route('/api/jms_complex_dual', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(
            type: JMSDualComplex::class,
            groups: ['Default', 'complex' => ['User' => ['details']]]
        ))
    )]
    public function complexDualAction(): void
    {
    }

    #[Route('/api/jms_naming_strategy', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSNamingStrategyConstraints::class, groups: ['Default']))
    )]
    public function namingStrategyConstraintsAction(): void
    {
    }

    #[Route('/api/jms_chat', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSChat::class, groups: ['Default', 'members' => ['mini']]))
    )]
    public function chatAction(): void
    {
    }

    #[Route('/api/jms_picture', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSPicture::class, groups: ['mini']))
    )]
    public function pictureAction(): void
    {
    }

    #[Route('/api/jms_mini_user', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSChatUser::class, groups: ['mini']))
    )]
    public function minUserAction(): void
    {
    }

    #[Route('/api/jms_mini_user_nested', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSChatRoomUser::class, groups: ['mini', 'friend' => ['living' => ['Default']]]))
    )]
    public function minUserNestedAction(): void
    {
    }

    #[Route('/api/jms_enum', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: Article81::class))
    )]
    public function enum(): void
    {
    }

    #[Route('/api/jms_discriminator_map', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSAbstractUser::class))
    )]
    public function discriminatorMapAction(): void
    {
    }

    #[Route('/api/jms_enum_array', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSEnum::class))
    )]
    public function enumArrayAction(): void
    {
    }

    #[Route('/api/jms_ignored_property', methods: ['GET'])]
    #[OAS\Response(
        response: 200,
        description: 'Success',
        content: new OAS\MediaType\Json(ref: new Model(type: JMSIgnoredProperty::class))
    )]
    public function ignoredProperty(): void
    {
    }
}
