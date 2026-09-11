<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC\Attributes;

use OpenApi\Spec as OA;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;


#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class OperationAttachable extends OA\Attachable
{
    public function __construct(public MapRequestPayload $mapRequestPayload)
    {
        parent::__construct();
    }

    public function contained(): array
    {
        return [
            OA\Operation::class => 'attachables[]',
        ];
    }
}
