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

use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\SpecDescribers\Model;
use Nelmio\ApiDocBundle\Attribute\Operation;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\ArrayItems\Dictionary;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\ArrayItems\Foo;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article81;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\ArticleInterface;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\CompoundEntity;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityThroughNameConverter;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithAlternateType;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithFalsyDefaults;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithIgnoredProperty;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithNullableSchemaSet;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithObjectType;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithRef;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithTranslatable;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithUlid;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\EntityWithUuid;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\RangeInteger;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyConstraints;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyConstraintsWithValidationGroups;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyDiscriminator;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyDiscriminatorFileMapping;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\User;
use Nelmio\ApiDocBundle\Tests\Functional\EntityExcluded\Symfony7\SerializedNameEntity;
use Nelmio\ApiDocBundle\Tests\Functional\Form\DummyType;
use Nelmio\ApiDocBundle\Tests\Functional\Form\FormWithAlternateSchemaType;
use Nelmio\ApiDocBundle\Tests\Functional\Form\FormWithCsrfProtectionDisabledType;
use Nelmio\ApiDocBundle\Tests\Functional\Form\FormWithCsrfProtectionEnabledType;
use Nelmio\ApiDocBundle\Tests\Functional\Form\FormWithModel;
use Nelmio\ApiDocBundle\Tests\Functional\Form\FormWithRefType;
use Nelmio\ApiDocBundle\Tests\Functional\Form\UserType;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Spec as OAS;

#[Route('/api', name: 'api_', host: 'api.example.com')]
#[OAS\Components]
class ApiController
{
    #[OAS\Response(
        response: '200',
        description: 'Success',
        attachables: [
            new Model(type: Article::class, groups: ['light']),
        ],
    )]
    #[OAS\Parameter(ref: '#/components/parameters/test')]
    #[Route('/article/{id}', methods: ['GET'])]
    #[OAS\Parameter(name: 'Accept-Version', in: 'header', schema: new OAS\Schema(type: 'string'))]
    #[OAS\Parameter(name: 'Application-Name', in: 'header', schema: new OAS\Schema(type: 'string'))]
    public function fetchArticleAction(): void
    {
    }

    #[OAS\Operation\Get(
        responses: [
            new OAS\Response(
                response: '200',
                description: 'Success',
                attachables: [
                    new Model(type: ArticleInterface::class, groups: ['light']),
                ],
            ),
        ],
    )]
    #[OAS\Parameter(ref: '#/components/parameters/test')]
    #[Route('/article-interface/{id}', methods: ['GET'])]
    #[OAS\Parameter(name: 'Accept-Version', in: 'header', schema: new OAS\Schema(type: 'string'))]
    #[OAS\Parameter(name: 'Application-Name', in: 'header', schema: new OAS\Schema(type: 'string'))]
    public function fetchArticleInterfaceAction(): void
    {
    }

    #[Route('/swagger', methods: ['GET', 'LINK'])]
    #[Route('/swagger2', methods: ['GET'])]
    #[Operation([
        'responses' => [
            new OAS\Response(
                response: '201',
                description: 'An example resource',
            ),
        ],
    ])]
    #[OAS\Operation\Get(
        path: '/api/swagger2',
        parameters: [
            new OAS\Parameter(name: 'Accept-Version', in: 'header', schema: new OAS\Schema(type: 'string')),
        ],
    )]
    #[OAS\Operation\Post(
        path: '/api/swagger2',
        responses: [
            new OAS\Response(
                response: '203',
                description: 'but 203 is not actually allowed (wrong method)',
            ),
        ],
    )]
    public function swaggerAction(): void
    {
    }

    #[Route('/swagger/implicit', methods: ['GET', 'POST'])]
    #[OAS\Response(
        response: '201',
        description: 'Operation automatically detected',
        attachables: [
            new Model(type: User::class),
        ],
    )]
    #[OAS\RequestBody(
        description: 'This is a request body',
        content: new OAS\MediaType\Json(
            type: 'array',
            items: new OAS\Schema\Items(ref: '#/components/schemas/User'),
        ),
    )]
    #[OAS\Tag(name: 'implicit')]
    public function implicitSwaggerAction(): void
    {
    }

    #[Route('/test/users/{user}', methods: ['POST'], schemes: ['https'], requirements: ['user' => '/foo/'])]
    #[OAS\Response(
        response: '201',
        description: 'Operation automatically detected',
        attachables: [
            new Model(type: User::class),
        ],
    )]
    #[OAS\RequestBody(
        description: 'This is a request body',
        content: new Model(type: UserType::class, options: ['bar' => 'baz']),
    )]
    public function submitUserTypeAction(): void
    {
    }

    #[Route('/test/{user}', methods: ['GET'], schemes: ['https'], requirements: ['user' => '/foo/'])]
    #[OAS\Response(response: 200, description: 'sucessful')]
    public function userAction(): void
    {
    }

    /**
     * This action is deprecated.
     *
     * Please do not use this action.
     *
     * @deprecated
     */
    #[Route('/deprecated', methods: ['GET'])]
    public function deprecatedAction(): void
    {
    }

    /**
     * This action is not documented. It is excluded by the config.
     */
    #[Route('/admin', methods: ['GET'])]
    public function adminAction(): void
    {
    }

    #[OAS\Operation\Get(
        path: '/filtered',
        responses: [
            new OAS\Response(response: '201', description: ''),
        ],
    )]
    public function filteredAction(): void
    {
    }

    #[Route('/form', methods: ['POST'])]
    #[OAS\RequestBody(
        description: 'Request content',
        content: new Model(type: DummyType::class),
    )]
    #[OAS\Response(response: 201, description: '')]
    public function formAction(): void
    {
    }

    #[Route('/form-model', methods: ['POST'])]
    #[OAS\RequestBody(
        description: 'Request content',
        content: new Model(type: FormWithModel::class),
    )]
    #[OAS\Response(response: 201, description: '')]
    public function formWithModelAction(): void
    {
    }

    #[Route('/form-model-renamed', methods: ['POST'])]
    #[OAS\RequestBody(
        description: 'Request content',
        content: new Model(type: FormWithModel::class, name: 'RenamedFormWithModel'),
    )]
    #[OAS\Response(response: 201, description: '')]
    public function formWithModelRenamed(): void
    {
    }

    #[Route('/security', methods: ['GET'])]
    #[OAS\Response(response: 201, description: '')]
    #[Security(name: 'api_key')]
    #[Security(name: 'basic')]
    #[Security(name: 'oauth2', scopes: ['scope_1'])]
    public function securityAction(): void
    {
    }

    #[Route('/securityOverride')]
    #[OAS\Response(response: 201, description: '')]
    #[Security(name: 'api_key')]
    #[Security(name: null)]
    public function securityActionOverride(): void
    {
    }

    #[Route('/swagger/symfonyConstraints', methods: ['GET'])]
    #[OAS\Response(
        response: '201',
        description: 'Used for symfony constraints test',
        content: new Model(type: SymfonyConstraints::class),
    )]
    public function symfonyConstraintsAction(): void
    {
    }

    #[OAS\Response(
        response: '200',
        description: 'Success',
        ref: '#/components/schemas/Test',
    )]
    #[OAS\Response(
        response: '201',
        ref: '#/components/responses/201',
    )]
    #[Route('/configReference', methods: ['GET'])]
    public function configReferenceAction(): void
    {
    }

    #[Route('/multi-annotations', methods: ['GET', 'POST'])]
    #[OAS\Operation\Get(description: 'This is the get operation')]
    #[OAS\Operation\Post(description: 'This is post')]
    #[OAS\Response(response: 200, description: 'Worked well!', attachables: [new Model(type: DummyType::class)])]
    public function operationsWithOtherAnnotations(): void
    {
    }

    #[Route('/areas/new', methods: ['GET', 'POST'])]
    #[Areas(['area', 'area2'])]
    public function newAreaAction(): void
    {
    }

    #[Route('/compound', methods: ['GET', 'POST'])]
    #[OAS\Response(response: 200, description: 'Worked well!', attachables: [new Model(type: CompoundEntity::class)])]
    public function compoundEntityAction(): void
    {
    }

    #[Route('/discriminator-mapping', methods: ['GET', 'POST'])]
    #[OAS\Response(response: 200, description: 'Worked well!', attachables: [new Model(type: SymfonyDiscriminator::class)])]
    public function discriminatorMappingAction(): void
    {
    }

    #[Route('/discriminator-mapping-configured-with-file', methods: ['GET', 'POST'])]
    #[OAS\Response(response: 200, description: 'Worked well!', attachables: [new Model(type: SymfonyDiscriminatorFileMapping::class)])]
    public function discriminatorMappingConfiguredWithFileAction(): void
    {
    }

    #[Route('/named_route-operation-id', name: 'named_route_operation_id', methods: ['GET', 'POST'])]
    #[OAS\Response(response: 200, description: 'success')]
    public function namedRouteOperationIdAction(): void
    {
    }

    #[Route('/custom-operation-id', methods: ['GET', 'POST'])]
    #[OAS\Operation\Get(operationId: 'get-custom-operation-id')]
    #[OAS\Operation\Post(operationId: 'post-custom-operation-id')]
    #[OAS\Response(response: 200, description: 'success')]
    public function customOperationIdAction(): void
    {
    }

    #[Route('/swagger/symfonyConstraintsWithValidationGroups', methods: ['GET'])]
    #[OAS\Response(
        response: '201',
        description: 'Used for symfony constraints with validation groups test',
        content: new Model(type: SymfonyConstraintsWithValidationGroups::class, groups: ['test']),
    )]
    public function symfonyConstraintsWithGroupsAction(): void
    {
    }

    #[Route('/alternate-entity-type', methods: ['GET', 'POST'])]
    #[OAS\Operation\Get(operationId: 'alternate-entity-type')]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithAlternateType::class),
        ),
    )]
    public function alternateEntityType(): void
    {
    }

    #[Route('/entity-with-ref', methods: ['GET', 'POST'])]
    #[OAS\Operation\Get(operationId: 'entity-with-ref')]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithRef::class),
        ),
    )]
    public function entityWithRef(): void
    {
    }

    #[Route('/entity-with-object-type', methods: ['GET', 'POST'])]
    #[OAS\Operation\Get(operationId: 'entity-with-object-type')]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithObjectType::class),
        ),
    )]
    public function entityWithObjectType(): void
    {
    }

    #[Route('/entity-with-uuid', methods: ['GET', 'POST'])]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithUuid::class),
        ),
    )]
    public function entityWithUuid(): void
    {
    }

    #[Route('/entity-with-ulid', methods: ['GET', 'POST'])]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithUlid::class),
        ),
    )]
    public function entityWithUlid(): void
    {
    }

    #[Route('/entity-with-translatable', methods: ['GET', 'POST'])]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithTranslatable::class),
        ),
    )]
    public function entityWithTranslatable(): void
    {
    }

    #[Route('/form-with-alternate-type', methods: ['POST'])]
    #[OAS\Response(
        response: 204,
        description: 'Operation automatically detected',
    )]
    #[OAS\RequestBody(
        content: new Model(type: FormWithAlternateSchemaType::class),
    )]
    public function formWithAlternateSchemaType(): void
    {
    }

    #[Route('/form-with-ref-type', methods: ['POST'])]
    #[OAS\Response(
        response: 204,
        description: 'Operation automatically detected',
    )]
    #[OAS\RequestBody(
        content: new Model(type: FormWithRefType::class),
    )]
    public function formWithRefSchemaType(): void
    {
    }

    #[Route('/form-with-csrf-protection-enabled-type', methods: ['POST'])]
    #[OAS\Response(
        response: 204,
        description: 'Operation automatically detected',
    )]
    #[OAS\RequestBody(
        content: new Model(type: FormWithCsrfProtectionEnabledType::class),
    )]
    public function formWithCsrfProtectionEnabledType(): void
    {
    }

    #[Route('/form-with-csrf-protection-disabled-type', methods: ['POST'])]
    #[OAS\Response(
        response: 204,
        description: 'Operation automatically detected',
    )]
    #[OAS\RequestBody(
        content: new Model(type: FormWithCsrfProtectionDisabledType::class),
    )]
    public function formWithCsrfProtectionDisabledType(): void
    {
    }

    #[Route('/entity-with-nullable-property-set', methods: ['GET'])]
    #[OAS\Response(
        response: 201,
        description: 'Operation automatically detected',
        attachables: [
            new Model(type: EntityWithNullableSchemaSet::class),
        ],
    )]
    public function entityWithNullableSchemaSet(): void
    {
    }

    #[Route('/entity-with-falsy-defaults', methods: ['GET'])]
    #[OAS\Response(
        response: 204,
        description: 'Operation automatically detected',
    )]
    #[OAS\RequestBody(
        content: new Model(type: EntityWithFalsyDefaults::class),
    )]
    public function entityWithFalsyDefaults(): void
    {
    }

    #[OAS\Operation\Get(responses: [
        new OAS\Response(
            response: '200',
            description: 'Success',
            attachables: [
                new Model(type: Article::class, groups: ['light']),
            ],
        ),
    ])]
    #[OAS\Parameter(ref: '#/components/parameters/test')]
    #[Route('/article_attributes/{id}', methods: ['GET'])]
    #[OAS\Parameter(name: 'Accept-Version', in: 'header', schema: new OAS\Schema(type: 'string'))]
    public function fetchArticleActionWithAttributes(): void
    {
    }

    #[Areas(['area', 'area2'])]
    #[Route('/areas_attributes/new', methods: ['GET', 'POST'])]
    public function newAreaActionAttributes(): void
    {
    }

    #[Route('/security_attributes')]
    #[OAS\Response(response: '201', description: '')]
    #[Security(name: 'api_key')]
    #[Security(name: 'basic')]
    #[Security(name: 'oauth2', scopes: ['scope_1'])]
    public function securityActionAttributes(): void
    {
    }

    #[Route('/security_override_attributes')]
    #[OAS\Response(response: '201', description: '')]
    #[Security(name: 'api_key')]
    #[Security(name: null)]
    public function securityOverrideActionAttributes(): void
    {
    }

    #[Route('/inline_path_parameters')]
    #[OAS\Response(response: '200', description: '')]
    public function inlinePathParameters(
        #[OAS\Parameter\Path]
        string $product_id,
    ): void {
    }

    #[Route('/enum')]
    #[OAS\Response(response: '201', description: '', attachables: [new Model(type: Article81::class)])]
    public function enum(): void
    {
    }

    #[Route('/range_integer', methods: ['GET'])]
    #[OAS\Response(response: '200', description: '', attachables: [new Model(type: RangeInteger::class)])]
    public function rangeInteger(): void
    {
    }

    #[Route('/serializename', methods: ['GET'])]
    #[OAS\Response(response: 200, description: 'success', content: new Model(type: SerializedNameEntity::class))]
    public function serializedNameAction(): void
    {
    }

    #[Route('/name_converter_context', methods: ['GET'])]
    #[OAS\Response(
        response: '200',
        description: '',
        content: new Model(type: EntityThroughNameConverter::class, serializationContext: ['secret_name_converter_value' => true])
    )]
    #[OAS\Response(
        response: '201',
        description: 'Same class without context',
        content: new Model(type: EntityThroughNameConverter::class)
    )]
    public function nameConverterContext(): void
    {
    }

    #[Route('/arbitrary_array', methods: ['GET'])]
    #[OAS\Response(response: 200, description: 'Success', content: new Model(type: Foo::class))]
    public function arbitraryArray(): void
    {
    }

    #[Route('/dictionary', methods: ['GET'])]
    #[OAS\Response(response: 200, description: 'Success', content: new Model(type: Dictionary::class))]
    public function dictionary(): void
    {
    }

    #[Route('/entity-with-ignored-property', methods: ['GET', 'POST'])]
    #[OAS\Response(
        response: 200,
        description: 'success',
        content: new OAS\MediaType\Json(
            ref: new Model(type: EntityWithIgnoredProperty::class),
        ),
    )]
    public function entityWithIgnoredProperty(): void
    {
    }

    #[Route('/swagger/model-parameter', methods: 'GET')]
    #[OAS\Parameter(name: 'user', in: 'query', schema: new OAS\Schema(ref: new Model(type: User::class)))] // nelmio: content: Model on a parameter means its schema
    #[OAS\Parameter(
        name: 'list',
        in: 'query',
        schema: new OAS\Schema(
            type: 'array',
            items: new OAS\Schema\Items(ref: new Model(type: Article::class, groups: ['light'])),
        ),
    )]
    public function modelParameter(): void
    {
    }
}
