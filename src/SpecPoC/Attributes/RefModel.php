<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Attributes;

use OpenApi\Spec as OA;

#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class RefModel extends OA\Schema\Ref
{

}
