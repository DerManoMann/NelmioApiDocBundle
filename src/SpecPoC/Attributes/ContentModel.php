<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Attributes;

use OpenApi\Spec as OA;

#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class ContentModel extends OA\MediaType\Json
{
    public function __construct(string $of)
    {
        // OA\MediaType has no ref; the Json shortcut does. RefModel, so that
        // ModelAugmenter collects the class while resolving the ref.
        parent::__construct(ref: new RefModel($of));
    }
}
