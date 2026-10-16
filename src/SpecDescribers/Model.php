<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use Nelmio\ApiDocBundle\Model\Model as RegistryModel;
use Nelmio\ApiDocBundle\Util\LegacyTypeConverter;
use OpenApi\Spec\Schema\Ref;

/**
 * nelmio's #[Model] as a spec attribute: it *is* a Schema\Ref, so it type-checks wherever spec
 * accepts a ref (`ref:` on a schema, a property, items, or a MediaType shortcut). Its `ref` is the
 * class name until ModelContribution swaps in the registry's component.
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class Model extends Ref
{
    private readonly RegistryModel $model;

    /**
     * `type` keeps nelmio's argument name; it cannot be a property, since Schema::$type is the
     * JSON Schema keyword (finding 9).
     */
    public function __construct(
        string $type,
        ?array $groups = null,
        array $options = [],
        array $serializationContext = [],
        ?string $name = null,
    ) {
        parent::__construct(ref: $type);
        $this->model = new RegistryModel(LegacyTypeConverter::createType($type), $groups, $options, $serializationContext, $name);
    }

    public function toRegistryModel(): RegistryModel
    {
        return $this->model;
    }
}
